import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "C:/Users/Jed Koops/projects/dental-reports/outputs/hygiene-perio-recare-2025-2026";
const outputPath = path.join(outputDir, "Hygiene Perio Recare Coach Report May 2025-Apr 2026.xlsx");

const months = [
  { key: "2025-05-01", label: "May 2025", hygiene: 69508.00, perio: 4440.00, d4341: 700.00, d4342: 0.00, d4910: 3740.00 },
  { key: "2025-06-01", label: "Jun 2025", hygiene: 75981.00, perio: 4861.00, d4341: 700.00, d4342: 1052.00, d4910: 3109.00 },
  { key: "2025-07-01", label: "Jul 2025", hygiene: 82256.00, perio: 6331.00, d4341: 2100.00, d4342: 1052.00, d4910: 3179.00 },
  { key: "2025-08-01", label: "Aug 2025", hygiene: 73651.00, perio: 3920.00, d4341: 700.00, d4342: 789.00, d4910: 2431.00 },
  { key: "2025-09-01", label: "Sep 2025", hygiene: 65797.00, perio: 9833.00, d4341: 3358.00, d4342: 1052.00, d4910: 5423.00 },
  { key: "2025-10-01", label: "Oct 2025", hygiene: 73961.00, perio: 7277.00, d4341: 2374.00, d4342: 789.00, d4910: 4114.00 },
  { key: "2025-11-01", label: "Nov 2025", hygiene: 63612.00, perio: 4696.00, d4341: 1400.00, d4342: 1052.00, d4910: 2244.00 },
  { key: "2025-12-01", label: "Dec 2025", hygiene: 70207.91, perio: 3822.00, d4341: 0.00, d4342: 1578.00, d4910: 2244.00 },
  { key: "2026-01-01", label: "Jan 2026", hygiene: 65780.00, perio: 6713.00, d4341: 2944.00, d4342: 1104.00, d4910: 2665.00 },
  { key: "2026-02-01", label: "Feb 2026", hygiene: 66058.91, perio: 7337.00, d4341: 1840.00, d4342: 1656.00, d4910: 3841.00 },
  { key: "2026-03-01", label: "Mar 2026", hygiene: 84996.00, perio: 9373.00, d4341: 2576.00, d4342: 2760.00, d4910: 4037.00 },
  { key: "2026-04-01", label: "Apr 2026", hygiene: 82357.52, perio: 6456.52, d4341: 1236.52, d4342: 1104.00, d4910: 4116.00 },
];

const recare = {
  asOf: "May 19, 2026",
  activeAdults: 4216,
  activeChildren: 463,
  adult6Mo: 3746,
  adult4Mo: 217,
  adult3Mo: 30,
  child6Mo: 422,
};

const colors = {
  ink: "#20343B",
  muted: "#5F6F73",
  header: "#0F5D65",
  soft: "#EEF6F7",
  total: "#E7F4EA",
  band: "#F8FBFB",
  note: "#FFF8E8",
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

function styleTitle(sheet, range, title, subtitle, subtitleRange = null) {
  sheet.getRange(range).merge();
  sheet.getRange(range).values = [[title]];
  sheet.getRange(range).format = {
    fill: colors.header,
    font: { bold: true, color: colors.white },
  };
  sheet.getRange(range).format.rowHeightPx = 34;
  if (subtitle) {
    const subRange = subtitleRange || range.replace(/1/g, "2");
    sheet.getRange(subRange).merge();
    sheet.getRange("A2").values = [[subtitle]];
    sheet.getRange("A2").format = {
      fill: colors.soft,
      font: { color: colors.muted },
    };
    sheet.getRange("A2").format.rowHeightPx = 24;
  }
}

function styleTable(sheet, headerRange, totalRange = null) {
  sheet.getRange(headerRange).format = {
    fill: colors.header,
    font: { bold: true, color: colors.white },
    wrapText: true,
  };
  sheet.getRange(headerRange).format.rowHeightPx = 40;
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

// Summary
styleTitle(
  summary,
  "A1:D1",
  "Hygiene, Perio, and Recare Coach Report",
  "Production by month: May 1, 2025 through April 30, 2026. Recare snapshot as of May 19, 2026.",
  "A2:D2"
);
setWidths(summary, [118, 160, 154, 138, 26, 230, 90, 320]);
summary.getRange("A4:D4").values = [["Month", "Total Hygiene Production", "Perio Code Production", "Perio % of Hygiene"]];
summary.getRange("A5:D16").values = months.map((row) => [row.label, row.hygiene, row.perio, null]);
summary.getRange("D5").formulas = [["=IFERROR(C5/B5,0)"]];
summary.getRange("D5:D16").fillDown();
summary.getRange("A17:D17").values = [["Year Total", null, null, null]];
summary.getRange("B17:D17").formulas = [["=SUM(B5:B16)", "=SUM(C5:C16)", "=IFERROR(C17/B17,0)"]];
summary.getRange("B5:C17").format.numberFormat = "$#,##0.00";
summary.getRange("D5:D17").format.numberFormat = "0.0%";
summary.getRange("A5:D16").format = { fill: colors.band };
styleTable(summary, "A4:D4", "A17:D17");
summary.freezePanes.freezeRows(4);
summary.tables.add("A4:D17", true, "MonthlyProduction");

const recareRows = [
  ["Total active adult patients", recare.activeAdults, "Active patients age 18+ as of May 19, 2026"],
  ["Total active child patients", recare.activeChildren, "Active patients under 18 as of May 19, 2026"],
  ["Adults on 6 mo recare", recare.adult6Mo, "Enabled Prophy/Perio recall interval bucket = 6 months"],
  ["Adults on 4 mo recare", recare.adult4Mo, "Enabled Prophy/Perio recall interval bucket = 4 months"],
  ["Adults on 3 mo recare", recare.adult3Mo, "Enabled Prophy/Perio recall interval bucket = 3 months"],
  ["Children on 6 mo recare", recare.child6Mo, "Enabled six-month recall; child prophy rows are stored under Prophy in this data"],
];
summary.getRange("F4:G4").values = [["Recare / Active Patient Snapshot", "Count"]];
summary.getRange("F5:G10").values = recareRows.map((row) => [row[0], row[1]]);
summary.getRange("F4:G4").format = {
  fill: colors.header,
  font: { bold: true, color: colors.white },
  wrapText: true,
};
summary.getRange("F5:G10").format = { fill: colors.note };
summary.getRange("G5:G10").format.numberFormat = "0";
summary.getRange("F4:G10").format.autofitRows();

summary.getRange("F13:H16").values = [
  ["Annual metric", "Value", "Notes"],
  ["Total hygiene production", sum("hygiene"), "Provider IsSecondary = 1"],
  ["Total perio code production", sum("perio"), "D4341 + D4342 + D4910"],
  ["Perio share of hygiene", null, "Total perio / total hygiene"],
];
summary.getRange("G16").formulas = [["=IFERROR(G15/G14,0)"]];
summary.getRange("G14:G15").format.numberFormat = "$#,##0.00";
summary.getRange("G16").format.numberFormat = "0.0%";
summary.getRange("F13:H13").format = {
  fill: colors.header,
  font: { bold: true, color: colors.white },
};
summary.getRange("F14:H16").format = { fill: colors.total, wrapText: true };

// Trend chart
styleTitle(
  trend,
  "A1:E1",
  "Monthly Trend",
  "Hygiene production, perio code production, and perio share of hygiene",
  "A2:E2"
);
setWidths(trend, [118, 150, 150, 122, 20, 130, 130, 130, 130, 130]);
trend.getRange("A4:D4").values = [["Month", "Hygiene Production", "Perio Production", "Perio %"]];
trend.getRange("A5:D16").values = months.map((row) => [row.label, row.hygiene, row.perio, null]);
trend.getRange("D5").formulas = [["=IFERROR(C5/B5,0)"]];
trend.getRange("D5:D16").fillDown();
trend.getRange("A17:D17").values = [["Year Total", sum("hygiene"), sum("perio"), null]];
trend.getRange("D17").formulas = [["=IFERROR(C17/B17,0)"]];
trend.getRange("B5:C17").format.numberFormat = "$#,##0.00";
trend.getRange("D5:D17").format.numberFormat = "0.0%";
trend.getRange("A5:D16").format = { fill: colors.band };
styleTable(trend, "A4:D4", "A17:D17");
trend.tables.add("A4:D17", true, "TrendMonthlyProduction");
trend.charts.add("line", {
  title: "Monthly Production Trend",
  categories: months.map((row) => row.label),
  series: [
    { name: "Hygiene Production", values: months.map((row) => row.hygiene) },
    { name: "Perio Production", values: months.map((row) => row.perio) },
  ],
  hasLegend: true,
  legend: { position: "bottom" },
  from: { row: 3, col: 5 },
  extent: { widthPx: 700, heightPx: 300 },
});
trend.charts.add("line", {
  title: "Perio Production as % of Hygiene",
  categories: months.map((row) => row.label),
  series: [
    { name: "Perio %", values: months.map((row) => (row.perio / row.hygiene) * 100) },
  ],
  hasLegend: false,
  from: { row: 20, col: 5 },
  extent: { widthPx: 700, heightPx: 260 },
});

// Source Data
styleTitle(
  source,
  "A1:H1",
  "Source Aggregate Data",
  "OpenDental Local API aggregate output; no patient names or procedure-level identifiers included.",
  "A2:H2"
);
setWidths(source, [94, 96, 136, 108, 108, 108, 126, 116, 24, 190, 72, 520]);
source.getRange("A4:H4").values = [[
  "MonthStart",
  "Month",
  "Hygiene Production",
  "D4341 Production",
  "D4342 Production",
  "D4910 Production",
  "Perio Production",
  "Perio %",
]];
source.getRange("A5:H16").values = months.map((row) => [
  row.key,
  row.label,
  row.hygiene,
  row.d4341,
  row.d4342,
  row.d4910,
  row.perio,
  null,
]);
source.getRange("H5").formulas = [["=IFERROR(G5/C5,0)"]];
source.getRange("H5:H16").fillDown();
source.getRange("A17:H17").values = [["Year Total", "", null, null, null, null, null, null]];
source.getRange("C17:H17").formulas = [["=SUM(C5:C16)", "=SUM(D5:D16)", "=SUM(E5:E16)", "=SUM(F5:F16)", "=SUM(G5:G16)", "=IFERROR(G17/C17,0)"]];
source.getRange("A5:A16").setNumberFormat("yyyy-mm-dd");
source.getRange("C5:G17").format.numberFormat = "$#,##0.00";
source.getRange("H5:H17").format.numberFormat = "0.0%";
source.getRange("A5:H16").format = { fill: colors.band };
styleTable(source, "A4:H4", "A17:H17");
source.freezePanes.freezeRows(4);
source.tables.add("A4:H17", true, "SourceMonthlyProduction");

source.getRange("J20:L20").values = [["Recare Snapshot", "Count", "Definition"]];
source.getRange("J21:L26").values = recareRows;
source.getRange("J20:L20").format = {
  fill: colors.header,
  font: { bold: true, color: colors.white },
  wrapText: true,
};
source.getRange("J21:L26").format = { fill: colors.note, wrapText: true };
source.getRange("K21:K26").format.numberFormat = "0";
source.getRange("J21:L26").format.rowHeightPx = 44;

// Notes
styleTitle(notes, "A1:B1", "Method Notes", "Definitions and assumptions used for this report", "A2:B2");
setWidths(notes, [190, 740]);
notes.getRange("A4:B13").values = [
  ["Report window", "May 1, 2025 through April 30, 2026 for monthly production."],
  ["Recare snapshot date", "May 19, 2026."],
  ["Hygiene production", "Completed procedurelog rows where provider.IsSecondary = 1. Production formula is ProcFee * (UnitQty + BaseUnits), matching the app's monthly report convention."],
  ["Perio code production", "Completed procedurelog rows with procedure codes D4341, D4342, or D4910. The request listed 4342 twice; this report uses the same perio set from the prior workbook."],
  ["Perio %", "Perio code production divided by total hygiene production for the month."],
  ["Active patients", "Patients where patient.PatStatus = 0; adult/child split uses age 18 as of May 19, 2026."],
  ["Recare counts", "Distinct active patients with enabled recall rows where IsDisabled = 0 and RecallTypeNum is Prophy or Perio."],
  ["Recall interval buckets", "OpenDental recall intervals were bucketed by encoded month value: 3, 4, or 6 months. Values with zero or one day in the lower component are included in the same month bucket."],
  ["Child 6 mo recare", "The active child six-month recall count is 422. In this database, child recall rows appear under the Prophy recall type rather than the separate Child Prophy recall type."],
  ["Privacy", "Workbook contains aggregate counts and dollars only, not patient-level detail."],
];
notes.getRange("A4:A13").format = {
  fill: "#DDEEEF",
  font: { bold: true, color: colors.ink },
};
notes.getRange("B4:B13").format = { wrapText: true };
notes.getRange("A4:B13").format.rowHeightPx = 48;

await fs.mkdir(outputDir, { recursive: true });

const inspection = await workbook.inspect({
  kind: "table",
  range: "Summary!A4:D17",
  include: "values,formulas",
  tableMaxRows: 20,
  tableMaxCols: 8,
});
console.log(inspection.ndjson);

const recareInspect = await workbook.inspect({
  kind: "table",
  range: "Summary!F4:G10",
  include: "values",
  tableMaxRows: 10,
  tableMaxCols: 4,
});
console.log(recareInspect.ndjson);

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
  ["Summary", "A1:H17"],
  ["Trend Chart", "A1:N34"],
  ["Source Data", "A1:L26"],
  ["Notes", "A1:B13"],
]) {
  const preview = await workbook.render({ sheetName, range, scale: 1, format: "png" });
  const bytes = new Uint8Array(await preview.arrayBuffer());
  await fs.writeFile(path.join(outputDir, `${sheetName.replace(/\s+/g, "-").toLowerCase()}.png`), bytes);
}

console.log(outputPath);
