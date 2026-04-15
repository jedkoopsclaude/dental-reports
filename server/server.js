require('dotenv').config({ path: '../.env' });

const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

// Env var validation — warn but don't crash
const requiredEnvVars = ['OD_API_PORT', 'OD_DEVELOPER_KEY', 'OD_USER_KEY', 'OPENAI_API_KEY'];
for (const key of requiredEnvVars) {
  if (!process.env[key]) {
    console.warn(`[WARN] Missing environment variable: ${key}`);
  }
}

const OD_API_PORT = process.env.OD_API_PORT;
const OD_DEVELOPER_KEY = process.env.OD_DEVELOPER_KEY;
const OD_USER_KEY = process.env.OD_USER_KEY;
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

// Middleware
app.use(express.json());

// CORS — allow all origins for dev
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(204);
  }
  next();
});

// Request logger
app.use((req, res, next) => {
  res.on('finish', () => {
    console.log(`[${req.method}] ${req.path} - ${res.statusCode}`);
  });
  next();
});

// GET / — serve index.html from parent directory
app.get('/', (req, res) => {
  res.sendFile(path.resolve(__dirname, '../index.html'));
});

// POST /api/query — proxy SQL queries to OpenDental Local API
app.post('/api/query', async (req, res) => {
  const { sql } = req.body;

  if (!sql || typeof sql !== 'string') {
    return res.status(400).json({ error: 'Request body must include a "sql" string.' });
  }

  // Safety check: only allow SELECT statements
  if (!/^\s*SELECT\s/i.test(sql)) {
    return res.status(400).json({ error: 'Only SELECT queries are permitted.' });
  }

  const odUrl = `http://localhost:${OD_API_PORT}/api/v1/queries/ShortQuery`;

  try {
    const response = await fetch(odUrl, {
      method: 'PUT',
      headers: {
        'Authorization': `ODFHIR ${OD_DEVELOPER_KEY}/${OD_USER_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ SqlCommand: sql }),
    });

    const data = await response.json();

    if (!response.ok) {
      return res.status(response.status).json({ error: data?.message || 'OpenDental API error', details: data });
    }

    return res.json(data);
  } catch (err) {
    console.error('[/api/query] Error:', err.message);
    return res.status(500).json({ error: err.message });
  }
});

// Shared helper: call OpenAI chat completions
async function callOpenAI(messages) {
  const response = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${OPENAI_API_KEY}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ model: 'gpt-4o-mini', messages }),
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data?.error?.message || 'OpenAI API error');
  const sql = data.choices?.[0]?.message?.content?.trim();
  if (!sql) throw new Error('OpenAI returned an empty response.');
  return sql;
}

// POST /api/ai/fix — correct a failing SQL query using the database error
app.post('/api/ai/fix', async (req, res) => {
  const { sql, error } = req.body;
  if (!sql || !error) return res.status(400).json({ error: '"sql" and "error" are required.' });

  try {
    const fixed = await callOpenAI([
      {
        role: 'system',
        content: `You are a MySQL expert fixing broken OpenDental queries.
OpenDental table aliases to use (required):
  procedurelog pl, provider pr, adjustment adj, claimproc cp,
  claimpayment cy, paysplit ps, appointment apt, patient pt,
  labcase lc, laboratory lab, procedurecode pc

Rules:
- Never use alias 'p' — patient is 'pt', procedurelog is 'pl'
- There is no column called ProviderID — use ProvNum
- Only reference alias.Column for tables you actually JOIN
- Production formula: SUM(pl.ProcFee*(pl.UnitQty+pl.BaseUnits))
- Return only the corrected SQL, no explanation, no markdown.`,
      },
      {
        role: 'user',
        content: `This query failed with the error below. Fix it.\n\nSQL:\n${sql}\n\nError:\n${error}`,
      },
    ]);
    return res.json({ sql: fixed });
  } catch (err) {
    console.error('[/api/ai/fix] Error:', err.message);
    return res.status(500).json({ error: err.message });
  }
});

// POST /api/ai — translate natural language to SQL via OpenAI
app.post('/api/ai', async (req, res) => {
  const { prompt, month } = req.body;

  if (!prompt || typeof prompt !== 'string') {
    return res.status(400).json({ error: 'Request body must include a "prompt" string.' });
  }

  const systemPrompt = `You are a SQL expert for OpenDental dental practice management software.
Generate a single SELECT query only (no INSERT/UPDATE/DELETE/DROP).
Database: opendental

Key tables and their REQUIRED aliases (always use these exact aliases):
- procedurelog pl  (ProcNum, PatNum, ProvNum, ProcDate, ProcFee, UnitQty, BaseUnits, ProcStatus[2=complete], CodeNum)
- provider pr      (ProvNum, FName, LName, IsSecondary[1=hygienist])
- adjustment adj   (AdjNum, PatNum, AdjDate, AdjAmt, ProvNum)
- claimproc cp     (ClaimProcNum, ClaimNum, PatNum, ProvNum, status[1,4,5,7=paid], InsPayAmt, Writeoff, DateCp, ClaimPaymentNum)
- claimpayment cy  (ClaimPaymentNum, CheckDate, InsPayAmt)
- paysplit ps      (SplitNum, PatNum, ProvNum, DatePay, SplitAmt)
- appointment apt  (AptNum, PatNum, ProvNum, AptDateTime, AptStatus[2=complete], Pattern, Note)
- patient pt       (PatNum, FName, LName, Birthdate, PatStatus)
- labcase lc       (LabCaseNum, PatNum, ProvNum, LaboratoryNum, AptNum, DateTimeRecd, LabFee)
- laboratory lab   (LaboratoryNum, LaborNum, Description)
- procedurecode pc (CodeNum, ProcCode, Descript)

CRITICAL RULES:
1. ONLY reference alias.Column for tables you have actually JOINed in the query.
2. Production formula: SUM(pl.ProcFee * (pl.UnitQty + pl.BaseUnits)) — always include BaseUnits.
3. Never use alias 'p' — patient table alias is 'pt'.
4. Every column in SELECT and GROUP BY that uses a table alias must match a table that is joined.

Example — production by provider:
SELECT pl.ProvNum, CONCAT(pr.FName,' ',pr.LName) AS ProviderName,
       SUM(pl.ProcFee*(pl.UnitQty+pl.BaseUnits)) AS TotalProduction
FROM procedurelog pl
JOIN provider pr ON pl.ProvNum=pr.ProvNum
WHERE pl.ProcStatus=2
GROUP BY pl.ProvNum, pr.FName, pr.LName
ORDER BY TotalProduction DESC

Key provider IDs: Jed Koops=43, Dr. Thanh Mollica (Associate)=216
Crown/bridge codes: D2740, D6058, D6740, D6245, D6548

Return only the SQL query with no explanation, no markdown, no code blocks.`;

  const userMessage = month
    ? `Month: ${month}\n\n${prompt}`
    : prompt;

  try {
    const sql = await callOpenAI([
      { role: 'system', content: systemPrompt },
      { role: 'user', content: userMessage },
    ]);
    return res.json({ sql });
  } catch (err) {
    console.error('[/api/ai] Error:', err.message);
    return res.status(500).json({ error: err.message });
  }
});

const OVERRIDES_PATH = path.join(__dirname, 'data', 'hours-overrides.json');

function readOverrides() {
  try { return JSON.parse(fs.readFileSync(OVERRIDES_PATH, 'utf8')); }
  catch { return {}; }
}

function writeOverrides(data) {
  const dir = path.dirname(OVERRIDES_PATH);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(OVERRIDES_PATH, JSON.stringify(data, null, 2));
}

// GET /api/hours-overrides?provNum=43 — returns overrides (optionally filtered)
app.get('/api/hours-overrides', (req, res) => {
  const overrides = readOverrides();
  const { provNum } = req.query;
  if (provNum) {
    const prefix = provNum + ':';
    const filtered = {};
    for (const [k, v] of Object.entries(overrides)) {
      if (k.startsWith(prefix)) filtered[k] = v;
    }
    return res.json(filtered);
  }
  return res.json(overrides);
});

// POST /api/hours-overrides — upsert { provNum, date, hours }
app.post('/api/hours-overrides', (req, res) => {
  const { provNum, date, hours } = req.body;
  if (!provNum || !date || hours == null) {
    return res.status(400).json({ error: 'provNum, date, and hours are required.' });
  }
  const overrides = readOverrides();
  overrides[`${provNum}:${date}`] = parseFloat(hours);
  writeOverrides(overrides);
  return res.json({ ok: true });
});

// DELETE /api/hours-overrides — remove { provNum, date }
app.delete('/api/hours-overrides', (req, res) => {
  const { provNum, date } = req.body;
  if (!provNum || !date) {
    return res.status(400).json({ error: 'provNum and date are required.' });
  }
  const overrides = readOverrides();
  delete overrides[`${provNum}:${date}`];
  writeOverrides(overrides);
  return res.json({ ok: true });
});

// Start server
app.listen(PORT, () => {
  console.log(`[INFO] dental-reports server listening on http://localhost:${PORT}`);
});
