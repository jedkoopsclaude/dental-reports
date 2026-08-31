import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "C:/Users/Jed Koops/projects/dental-reports/outputs/coach-cdt-report-2025-2026-v2";
const outputPath = path.join(outputDir, "Coach CDT Code Report May 2025-Apr 2026 v2.xlsx");

const months = [
  { key: "2025-05-01", label: "May 2025", d0150Adult: 23, d0150Under18: 6, d0150All: 29, d1110: 299, d1120: 22, d4341: 2, d4342: 0, d4910: 20 },
  { key: "2025-06-01", label: "Jun 2025", d0150Adult: 26, d0150Under18: 4, d0150All: 30, d1110: 290, d1120: 34, d4341: 2, d4342: 4, d4910: 17 },
  { key: "2025-07-01", label: "Jul 2025", d0150Adult: 28, d0150Under18: 11, d0150All: 39, d1110: 327, d1120: 31, d4341: 6, d4342: 4, d4910: 17 },
  { key: "2025-08-01", label: "Aug 2025", d0150Adult: 36, d0150Under18: 12, d0150All: 48, d1110: 281, d1120: 38, d4341: 2, d4342: 3, d4910: 13 },
  { key: "2025-09-01", label: "Sep 2025", d0150Adult: 29, d0150Under18: 8, d0150All: 37, d1110: 250, d1120: 25, d4341: 10, d4342: 4, d4910: 29 },
  { key: "2025-10-01", label: "Oct 2025", d0150Adult: 27, d0150Under18: 7, d0150All: 34, d1110: 304, d1120: 33, d4341: 8, d4342: 3, d4910: 22 },
  { key: "2025-11-01", label: "Nov 2025", d0150Adult: 29, d0150Under18: 9, d0150All: 38, d1110: 276, d1120: 24, d4341: 4, d4342: 4, d4910: 12 },
  { key: "2025-12-01", label: "Dec 2025", d0150Adult: 21, d0150Under18: 5, d0150All: 26, d1110: 283, d1120: 24, d4341: 0, d4342: 6, d4910: 12 },
  { key: "2026-01-01", label: "Jan 2026", d0150Adult: 29, d0150Under18: 9, d0150All: 38, d1110: 236, d1120: 35, d4341: 8, d4342: 4, d4910: 14 },
  { key: "2026-02-01", label: "Feb 2026", d0150Adult: 19, d0150Under18: 3, d0150All: 22, d1110: 267, d1120: 17, d4341: 5, d4342: 6, d4910: 20 },
  { key: "2026-03-01", label: "Mar 2026", d0150Adult: 29, d0150Under18: 5, d0150All: 34, d1110: 319, d1120: 37, d4341: 8, d4342: 10, d4910: 21 },
  { key: "2026-04-01", label: "Apr 2026", d0150Adult: 27, d0150Under18: 5, d0150All: 32, d1110: 305, d1120: 35, d4341: 4, d4342: 4, d4910: 21 },
];

const colors = {
  ink: "#20343B",
  muted: "#5F6F73",
  header: "#0F5D65",
  header2: "#DDEEEF",
  line: "#A8BFC2",
  total: "#E7F4EA",
  band: "#F8FBFB",
  accent: "#D9A441",
  white: "#FFFFFF",
};

function sum(key) {
  return months.reduce((acc, row) => acc + row[key], 0);
}

function setWidths(sheet, widths) {
  widths.forEach((width, index) => {
    sheet.getCell(0, index).format.columnWidthPx = width;
  });
}

function styleTitle(sheet, range, title, subtitle, subtitleRange = "A2:I2") {
  sheet.getRange(range).merge();
  sheet.getRange(range).values = [[title]];
  sheet.getRange(range).format = {
    fill: colors.header,
    font: { bold: true, color: colors.white },
    horizontalAlignment: "left",
    verticalAlignment: "center",
  };
  sheet.getRange(range).format.rowHeightPx = 34;
  if (subtitle) {
    sheet.getRange(subtitleRange).merge();
    sheet.getRange("A2").values = [[subtitle]];
    sheet.getRange("A2").format = {
      fill: "#EEF6F7",
      font: { color: colors.muted },
      horizontalAlignment: "left",
      verticalAlignment: "center",
    };
    sheet.getRange("A2").format.rowHeightPx = 24;
  }
}

function styleTable(sheet, range, headerRange, totalRange = null) {
  sheet.getRange(headerRange).format = {
    fill: colors.header,
    font: { bold: true, color: colors.white },
    wrapText: true,
  };
  sheet.getRange(headerRange).format.rowHeightPx = 42;
  if (totalRange) {
    sheet.getRange(totalRange).format = {
      fill: colors.total,
      font: { bold: true, color: colors.ink },
    };
  }
}

const workbook = Workbook.create();
const summary = workbook.worksheets.add("Summary");
const trend = workbook.worksheets.add("Trend Chart");
const source = workbook.worksheets.add("Source Data");
const notes = workbook.worksheets.add("Notes");

for (const sheet of [summary, trend, source, notes]) {
  sheet.showGridLines = false;
}

// Summary sheet
styleTitle(
  summary,
  "A1:K1",
  "Coach CDT Code Report",
  "Completed OpenDental procedure codes grouped by ProcDate, May 1, 2025 through April 30, 2026",
  "A2:K2"
);
setWidths(summary, [142, 76, 76, 76, 112, 116, 116, 96, 112, 112, 104]);

summary.getRange("A4:K4").values = [[
  "Month",
  "D4341",
  "D4342",
  "D4910",
  "Periodontal Total",
  "D0150 Over 18",
  "D0150 Under 18",
  "D0150 Total",
  "Adult Prophy D1110",
  "Child Prophy D1120",
  "Prophy Total",
]];

summary.getRange("A5:K16").values = months.map((row) => [
  row.label,
  row.d4341,
  row.d4342,
  row.d4910,
  null,
  row.d0150Adult,
  row.d0150Under18,
  null,
  row.d1110,
  row.d1120,
  null,
]);
summary.getRange("E5").formulas = [["=SUM(B5:D5)"]];
summary.getRange("E5:E16").fillDown();
summary.getRange("H5").formulas = [["=SUM(F5:G5)"]];
summary.getRange("H5:H16").fillDown();
summary.getRange("K5").formulas = [["=SUM(I5:J5)"]];
summary.getRange("K5:K16").fillDown();
summary.getRange("A17:K17").values = [["Year Total", null, null, null, null, null, null, null, null, null, null]];
summary.getRange("B17:K17").formulas = [["=SUM(B5:B16)", "=SUM(C5:C16)", "=SUM(D5:D16)", "=SUM(E5:E16)", "=SUM(F5:F16)", "=SUM(G5:G16)", "=SUM(H5:H16)", "=SUM(I5:I16)", "=SUM(J5:J16)", "=SUM(K5:K16)"]];
summary.getRange("B5:K17").format.numberFormat = "0";
summary.getRange("A5:A17").format = { font: { color: colors.ink } };
summary.getRange("A5:K16").format = { fill: colors.band };
styleTable(summary, "A4:K17", "A4:K4", "A17:K17");
summary.freezePanes.freezeRows(4);
summary.tables.add("A4:K17", true, "SummaryCounts");

summary.getRange("A20:C26").values = [
  ["Metric", "Annual Total", "Notes"],
  ["Periodontal codes", sum("d4341") + sum("d4342") + sum("d4910"), "D4341 + D4342 + D4910"],
  ["D0150 over 18", sum("d0150Adult"), "Patients age 18+ on ProcDate"],
  ["D0150 under 18", sum("d0150Under18"), "Patients under 18 on ProcDate"],
  ["D0150 total", sum("d0150All"), "Over 18 + under 18"],
  ["Adult prophy", sum("d1110"), "D1110"],
  ["Child prophy", sum("d1120"), "D1120"],
];
summary.getRange("C20:K26").merge(true);
summary.getRange("A20:K20").format = {
  fill: colors.header,
  font: { bold: true, color: colors.white },
};
summary.getRange("A21:K26").format = {
  fill: "#FFF8E8",
};
summary.getRange("B21:B26").format.numberFormat = "0";
summary.getRange("A20:K26").format.autofitRows();

// Trend chart sheet
styleTitle(
  trend,
  "A1:E1",
  "Monthly Trend Snapshot",
  "Periodontal total, D0150 age split, and prophy total by month",
  "A2:E2"
);
setWidths(trend, [110, 126, 126, 126, 126, 20, 120, 120, 120, 120]);
trend.getRange("A4:E4").values = [["Month", "Periodontal Total", "D0150 Over 18", "D0150 Under 18", "Prophy Total"]];
trend.getRange("A5:E16").values = months.map((row) => [
  row.label,
  row.d4341 + row.d4342 + row.d4910,
  row.d0150Adult,
  row.d0150Under18,
  row.d1110 + row.d1120,
]);
trend.getRange("A17:E17").values = [["Year Total", sum("d4341") + sum("d4342") + sum("d4910"), sum("d0150Adult"), sum("d0150Under18"), sum("d1110") + sum("d1120")]];
trend.getRange("B5:E17").format.numberFormat = "0";
styleTable(trend, "A4:E17", "A4:E4", "A17:E17");
trend.tables.add("A4:E17", true, "TrendCounts");
if (process.env.SKIP_CHART !== "1") {
  trend.charts.add("line", {
    title: "Monthly Code Volume Trend",
    categories: months.map((row) => row.label),
    series: [
      { name: "Periodontal Total", values: months.map((row) => row.d4341 + row.d4342 + row.d4910) },
      { name: "D0150 Over 18", values: months.map((row) => row.d0150Adult) },
      { name: "D0150 Under 18", values: months.map((row) => row.d0150Under18) },
      { name: "Prophy Total", values: months.map((row) => row.d1110 + row.d1120) },
    ],
    hasLegend: true,
    legend: { position: "bottom" },
    from: { row: 3, col: 5 },
    extent: { widthPx: 720, heightPx: 340 },
  });
}

// Source data sheet
styleTitle(
  source,
  "A1:L1",
  "Source Aggregate Data",
  "OpenDental Local API aggregate output; no patient names or procedure-level identifiers included",
  "A2:L2"
);
setWidths(source, [94, 92, 70, 70, 70, 92, 110, 104, 104, 104, 92, 110]);
source.getRange("A4:L4").values = [[
  "MonthStart",
  "Month",
  "D4341",
  "D4342",
  "D4910",
  "Perio Total",
  "D0150 Adult 18+",
  "D0150 Under 18",
  "D0150 All Ages",
  "D1110",
  "D1120",
  "Prophy Total",
]];
source.getRange("A5:L16").values = months.map((row) => [
  row.key,
  row.label,
  row.d4341,
  row.d4342,
  row.d4910,
  row.d4341 + row.d4342 + row.d4910,
  row.d0150Adult,
  row.d0150Under18,
  row.d0150All,
  row.d1110,
  row.d1120,
  row.d1110 + row.d1120,
]);
source.getRange("A17:L17").values = [["Year Total", "", null, null, null, null, null, null, null, null, null, null]];
source.getRange("C17:L17").formulas = [["=SUM(C5:C16)", "=SUM(D5:D16)", "=SUM(E5:E16)", "=SUM(F5:F16)", "=SUM(G5:G16)", "=SUM(H5:H16)", "=SUM(I5:I16)", "=SUM(J5:J16)", "=SUM(K5:K16)", "=SUM(L5:L16)"]];
source.getRange("A5:A16").setNumberFormat("yyyy-mm-dd");
source.getRange("C5:L17").format.numberFormat = "0";
styleTable(source, "A4:L17", "A4:L4", "A17:L17");
source.freezePanes.freezeRows(4);
source.tables.add("A4:L17", true, "SourceAggregateCounts");

// Notes sheet
styleTitle(notes, "A1:D1", "Method Notes", "Definitions and assumptions used for this report", "A2:D2");
setWidths(notes, [160, 680, 120, 120]);
notes.getRange("A4:B11").values = [
  ["Report window", "May 1, 2025 through April 30, 2026."],
  ["OpenDental source", "Completed procedurelog rows where ProcStatus = 2, joined to procedurecode by CodeNum and grouped by procedurelog.ProcDate."],
  ["Count method", "Each charged-out procedurelog row counts as one code occurrence."],
  ["Periodontal total", "D4341 + D4342 + D4910."],
  ["D0150 split", "D0150 rows are split into over 18 and under 18 based on patient age on the procedure date."],
  ["Adult prophy", "D1110."],
  ["Child prophy", "D1120. Verified as the child prophylaxis CDT code."],
  ["Privacy", "Workbook contains only aggregate monthly counts, not patient-level detail."],
];
notes.getRange("A4:A11").format = {
  fill: colors.header2,
  font: { bold: true, color: colors.ink },
  verticalAlignment: "top",
};
notes.getRange("B4:B11").format = {
  wrapText: true,
  verticalAlignment: "top",
};
notes.getRange("A4:B11").format.rowHeightPx = 42;
notes.getRange("A13:B15").values = [
  ["SQL summary", "Main code counts used pc.ProcCode IN ('D4341','D4342','D4910','D0150','D1110','D1120') and pl.ProcDate BETWEEN '2025-05-01' AND '2026-04-30'."],
  ["D0150 age split", "D0150 over 18 and under 18 counts used TIMESTAMPDIFF(YEAR, pt.Birthdate, pl.ProcDate)."],
  ["Child prophy source", "https://dentalcoding.com/cdt-code/d1120-prophylaxis-child/"],
];
notes.getRange("A13:A15").format = {
  fill: "#FFF8E8",
  font: { bold: true, color: colors.ink },
};
notes.getRange("B13:B15").format = { wrapText: true };
notes.getRange("A13:B15").format.rowHeightPx = 48;

await fs.mkdir(outputDir, { recursive: true });

const inspection = await workbook.inspect({
  kind: "table",
  range: "Summary!A4:K17",
  include: "values,formulas",
  tableMaxRows: 20,
  tableMaxCols: 12,
});
console.log(inspection.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 300 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

const xlsx = await SpreadsheetFile.exportXlsx(workbook);
await xlsx.save(outputPath);

for (const [sheetName, range] of [
  ["Summary", "A1:K26"],
  ["Trend Chart", "A1:N22"],
  ["Source Data", "A1:L17"],
  ["Notes", "A1:B15"],
]) {
  const preview = await workbook.render({ sheetName, range, scale: 1, format: "png" });
  const bytes = new Uint8Array(await preview.arrayBuffer());
  await fs.writeFile(path.join(outputDir, `${sheetName.replace(/\s+/g, "-").toLowerCase()}.png`), bytes);
}

console.log(outputPath);
