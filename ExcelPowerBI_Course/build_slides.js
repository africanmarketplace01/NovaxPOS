const pptxgen = require("pptxgenjs");
const fs = require("fs");
const path = require("path");

const OUT = path.join(__dirname, "slides");
fs.mkdirSync(OUT, { recursive: true });

// ---------------------------------------------------------------- palettes
const EXCEL = { dark: "0E3B21", primary: "217346", mid: "2E9E5B", light: "E6F2EA", ink: "1A1A1A", muted: "5A6B60", card: "F4F9F6" };
const PBI   = { dark: "3A2E00", primary: "C99700", mid: "E8B21E", light: "FBF3DC", ink: "1A1A1A", muted: "6B5E3A", card: "FBF7EC" };

const HEAD = "Cambria";     // safe-list serif header
const BODY = "Calibri";     // safe-list sans body

// ---------------------------------------------------------------- content
// Each week: {n, part, theme, palette, homework, sessions:[{code,title,objectives[],concepts[],demo[],notes}]}
const WEEKS = [
  { n: 1, part: "PART 1 · ADVANCED EXCEL", theme: "Foundations & Data Structuring", palette: EXCEL,
    homework: "Clean and structure the raw orders file into a proper Excel Table.",
    sessions: [
      { code: "S1", title: "Excel Power Features + Tables",
        objectives: ["Move fluently with keyboard shortcuts", "Use absolute vs relative references correctly", "Name ranges for readable formulas", "Convert data into structured Excel Tables"],
        concepts: ["Ribbon deep-dive & Quick Access Toolbar", "$A$1 vs A1 vs $A1 — when each locks", "Named ranges (Name Box & Name Manager)", "Insert > Table, table styles, Total row", "Structured references: Table[Column]", "Auto-expansion when new rows are added"],
        demo: ["Turn sales_data.csv into a formatted Table", "Add a calculated column with a structured reference", "Create a named range and use it in a SUM"],
        notes: "Emphasise WHY tables matter: formulas auto-fill, ranges auto-expand, and PivotTables/Power BI love them. Have learners retype a few shortcuts until muscle memory forms." },
      { code: "S2", title: "Data Cleaning Fundamentals",
        objectives: ["Split and combine text columns", "Remove duplicates and trailing spaces", "Standardise text case", "Use wildcards in Find & Replace"],
        concepts: ["Text-to-Columns (delimited & fixed width)", "Flash Fill (Ctrl+E) for pattern extraction", "Remove Duplicates", "TRIM, CLEAN, PROPER, UPPER, LOWER", "Find & Replace with * and ? wildcards", "Spotting hidden characters & inconsistent data"],
        demo: ["Clean raw_orders_messy.csv step by step", "Fix mixed-case names with PROPER + TRIM", "Extract first names with Flash Fill"],
        notes: "This is the highest-value real-world skill. Let learners feel the pain of the messy file first, then show each fix. Save the cleaned result for later sessions." },
      { code: "S3", title: "Logical Functions  +  Quiz 1",
        objectives: ["Write single and nested IF formulas", "Replace nested IFs with IFS", "Combine conditions with AND / OR / NOT", "Trap errors with IFERROR"],
        concepts: ["IF(logical_test, value_if_true, value_if_false)", "Nested IF and its readability limits", "IFS for multiple bands", "AND, OR, NOT for compound tests", "IFERROR to hide #N/A and #DIV/0!", "Building a commission / grading model"],
        demo: ["Build a sales-commission calculator", "Add a bonus tier with IFS", "Wrap a division in IFERROR"],
        notes: "End the session with Quiz 1 (10 questions) covering Weeks 1 topics. Collect homework briefs." },
    ] },
  { n: 2, part: "PART 1 · ADVANCED EXCEL", theme: "Lookup & Reference Mastery", palette: EXCEL,
    homework: "Create an employee record lookup sheet with a searchable ID box (from hr_data.csv).",
    sessions: [
      { code: "S4", title: "VLOOKUP, HLOOKUP & INDEX+MATCH",
        objectives: ["Retrieve values with VLOOKUP/HLOOKUP", "Understand exact vs approximate match", "Diagnose common lookup errors", "Build two-way lookups with INDEX+MATCH"],
        concepts: ["VLOOKUP anatomy & the 4th argument", "Why VLOOKUP breaks when columns move", "HLOOKUP for horizontal data", "INDEX(array, row, col)", "MATCH(value, array, 0)", "INDEX+MATCH for left-lookups & 2-way lookups"],
        demo: ["Look up product price from products.csv", "Rebuild the same lookup with INDEX+MATCH", "Break a VLOOKUP by inserting a column to show fragility"],
        notes: "Stress that INDEX+MATCH is column-insert-proof and can look left. This sets up XLOOKUP next session." },
      { code: "S5", title: "XLOOKUP, Dynamic Arrays & References",
        objectives: ["Use XLOOKUP and XMATCH", "Work with spilled dynamic arrays", "Use INDIRECT, OFFSET and CHOOSE", "Build dependent drop-down lists"],
        concepts: ["XLOOKUP: default exact match, if_not_found", "XMATCH improvements over MATCH", "Dynamic arrays: FILTER, SORT, UNIQUE, SEQUENCE", "The spill range and the # operator", "INDIRECT, OFFSET, CHOOSE", "Dependent Data Validation drop-downs"],
        demo: ["Replace earlier VLOOKUP with XLOOKUP", "Return multiple columns in one XLOOKUP spill", "Build Region → Salesperson dependent lists"],
        notes: "Note XLOOKUP needs Microsoft 365. If learners are on 2019, keep INDEX+MATCH as the fallback." },
      { code: "S6", title: "Lookup Lab  +  Quiz 2",
        objectives: ["Combine lookups into a working tool", "Handle missing values gracefully", "Validate inputs", "Assess Week 2 knowledge"],
        concepts: ["Product price-lookup mini-tool", "Employee record finder by ID", "IFERROR + XLOOKUP for clean output", "Data Validation to constrain inputs", "Lab troubleshooting patterns"],
        demo: ["Guided build of the price-lookup tool", "Independent build of the employee finder"],
        notes: "Run Quiz 2 at the end. Circulate during the lab; collect the employee-finder homework next week." },
    ] },
  { n: 3, part: "PART 1 · ADVANCED EXCEL", theme: "Analysis, PivotTables & Dashboards", palette: EXCEL,
    homework: "Build a one-page regional sales dashboard from sales_data.csv.",
    sessions: [
      { code: "S7", title: "PivotTables — Summarise Anything",
        objectives: ["Build PivotTables from Tables", "Group, sort and filter results", "Add calculated fields & value settings", "Add Slicers and Timelines"],
        concepts: ["Rows / Columns / Values / Filters zones", "Grouping dates into months & quarters", "Value settings: % of total, running total", "Calculated fields", "Slicers for point-and-click filtering", "Timelines for date filtering", "Refreshing when source data changes"],
        demo: ["Revenue by Region and Category", "Show Values As % of Grand Total", "Add a Slicer and a Timeline"],
        notes: "Reinforce that PivotTables need clean, tabular source data — link back to Week 1-2." },
      { code: "S8", title: "Charts & Conditional Formatting",
        objectives: ["Choose the right chart for the message", "Build combo and sparkline charts", "Apply rule-based conditional formatting", "Format with formulas"],
        concepts: ["Column / bar / line / combo charts", "Sparklines for in-cell trends", "Chart best practices (declutter, label)", "Data bars, colour scales, icon sets", "Top/bottom & duplicate-highlight rules", "Formula-driven conditional formatting"],
        demo: ["Combo chart: revenue bars + margin line", "Highlight below-target rows with a formula rule", "Add sparklines per salesperson"],
        notes: "Keep charts simple — one message per chart. Contrast a cluttered chart with a clean one." },
      { code: "S9", title: "Interactive Dashboard Build",
        objectives: ["Plan a one-page dashboard layout", "Wire Slicers to multiple PivotTables/charts", "Add KPI cells", "Polish for presentation"],
        concepts: ["Dashboard layout & visual hierarchy", "Connecting one Slicer to many PivotTables (Report Connections)", "KPI cells (Total Revenue, Top Region)", "Consistent colours & number formats", "Freezing panes & hiding gridlines"],
        demo: ["Assemble PivotTables + charts + Slicers on one sheet", "Link a single Slicer to all elements"],
        notes: "This mirrors the Excel capstone. Encourage a clean, printable one-pager." },
    ] },
  { n: 4, part: "PART 1 · ADVANCED EXCEL", theme: "Functions, Power Query & Excel Capstone", palette: EXCEL,
    homework: "Prepare for the Power BI half — install Power BI Desktop before Week 5.",
    sessions: [
      { code: "S10", title: "Text, Date & Statistical Functions",
        objectives: ["Manipulate text with function combos", "Calculate with dates and workdays", "Aggregate with conditional functions", "Rank and sub-total data"],
        concepts: ["LEFT, RIGHT, MID, LEN, TEXTJOIN, CONCAT", "DATEDIF, EOMONTH, NETWORKDAYS, TODAY", "SUMIFS, COUNTIFS, AVERAGEIFS", "SUBTOTAL vs SUM with filters", "AGGREGATE (ignore errors/hidden)", "RANK and RANK.EQ"],
        demo: ["Build full names & IDs with TEXTJOIN", "Tenure in years with DATEDIF on hr_data.csv", "Revenue by region with SUMIFS"],
        notes: "Show how SUMIFS often replaces a PivotTable for a single number on a dashboard." },
      { code: "S11", title: "Power Query, What-If & Macros",
        objectives: ["Import and transform data with Power Query", "Merge and append queries", "Model scenarios with What-If tools", "Record a basic macro"],
        concepts: ["Get & Transform: import, unpivot, split", "Merge (join) & Append (stack) queries", "Applied Steps & refresh", "Goal Seek & Scenario Manager", "Data Tables (one/two variable)", "Recording and running a simple macro"],
        demo: ["Clean raw_orders_messy.csv in Power Query", "Merge sales with products", "Goal Seek a target profit"],
        notes: "Frame Power Query as the bridge into Power BI — the engine is the same." },
      { code: "S12", title: "Excel Capstone + Mid-Course Assessment",
        objectives: ["Apply the full Excel workflow end-to-end", "Clean → analyse → visualise independently", "Present a finished dashboard", "Sit the graded mid-course assessment"],
        concepts: ["Brief: clean the messy orders, enrich with margins via XLOOKUP, build a dashboard", "Required: PivotTable, Slicer, combo chart, 3 KPI cells", "Grading rubric walkthrough", "Mid-course assessment (25%)"],
        demo: ["Instructor-led kickoff, then independent build"],
        notes: "Capstone brief is in the student workbook. Keep the last 45 min for the assessment." },
    ] },
  { n: 5, part: "PART 2 · POWER BI", theme: "Foundations & Data Preparation", palette: PBI,
    homework: "Connect to and clean the multi-file dataset folder; save the .pbix.",
    sessions: [
      { code: "S13", title: "Power BI Overview + Getting Data",
        objectives: ["Explain Desktop vs Service vs Mobile", "Navigate the Power BI Desktop interface", "Decide when Power BI beats Excel", "Connect to multiple data sources"],
        concepts: ["The Power BI ecosystem", "Report / Data / Model views", "When to use Power BI over Excel", "Get Data: Excel, CSV, Web, Folder", "The Navigator & data preview", "Saving a .pbix file"],
        demo: ["Install & tour Power BI Desktop", "Connect to sales_data + products + calendar", "Load vs Transform"],
        notes: "Confirm everyone has Power BI Desktop installed. It is Windows-only and free." },
      { code: "S14", title: "Power Query in Power BI",
        objectives: ["Use the Power Query Editor", "Apply and reorder transformation steps", "Set correct data types", "Reference vs duplicate queries"],
        concepts: ["Query Editor tour", "Common transforms: remove, rename, filter", "Data types & locale", "Applied Steps (edit, reorder, delete)", "Reference vs Duplicate", "Close & Apply"],
        demo: ["Promote headers, set types, remove nulls", "Reorder applied steps to see the effect"],
        notes: "Reassure Excel learners: this is the same engine as Excel's Get & Transform." },
      { code: "S15", title: "Shaping Data + Lab",
        objectives: ["Merge and append queries", "Pivot / unpivot columns", "Split columns cleanly", "Combine files from a folder"],
        concepts: ["Merge queries (join kinds)", "Append queries (stack tables)", "Pivot & Unpivot", "Split column by delimiter/position", "Combine files from a folder", "Removing errors & blanks"],
        demo: ["Merge sales with products on ProductID", "Unpivot a wide monthly table", "Lab: shape the full dataset for modelling"],
        notes: "The lab output feeds Week 6's model — have learners save a clean .pbix." },
    ] },
  { n: 6, part: "PART 2 · POWER BI", theme: "Data Modeling & DAX Basics", palette: PBI,
    homework: "Build a star schema and author 5 core measures on the sales model.",
    sessions: [
      { code: "S16", title: "Data Modeling & the Star Schema",
        objectives: ["Distinguish fact and dimension tables", "Design a star schema", "Create and manage relationships", "Understand cardinality & filter direction"],
        concepts: ["Fact vs dimension tables", "Star schema vs flat table", "Creating relationships (drag or dialog)", "Cardinality: 1-to-many, many-to-many", "Cross-filter direction", "Active vs inactive relationships"],
        demo: ["Relate sales → products and sales → calendar", "Inspect the Model view diagram"],
        notes: "Draw the star on the board. A good model makes DAX simple; a bad model makes it painful." },
      { code: "S17", title: "DAX Basics",
        objectives: ["Distinguish calculated columns and measures", "Write core aggregation measures", "Use CALCULATE and FILTER", "Explain row vs filter context"],
        concepts: ["Calculated columns vs measures (and when)", "SUM, AVERAGE, COUNT, DISTINCTCOUNT", "CALCULATE — the most important function", "FILTER inside CALCULATE", "IF and SWITCH in DAX", "Row context vs filter context"],
        demo: ["Total Revenue & Total Margin measures", "Revenue for one category via CALCULATE", "A SWITCH-based label measure"],
        notes: "Push measures over calculated columns as the default. Context is the hardest idea — go slow." },
      { code: "S18", title: "DAX Practice Lab  +  Quiz",
        objectives: ["Build a complete model independently", "Author 5 reusable measures", "Test measures in a matrix", "Assess DAX fundamentals"],
        concepts: ["Measures: Total Revenue, Total Cost, Margin, Margin %, Avg Order Value", "Testing measures in a matrix visual", "Formatting measures (currency, %)", "Debugging common DAX errors"],
        demo: ["Guided build of the 5 measures", "Independent extension: Orders count & Units sold"],
        notes: "Run the DAX quiz at the end of the session." },
    ] },
  { n: 7, part: "PART 2 · POWER BI", theme: "Advanced DAX & Visualization", palette: PBI,
    homework: "Add YoY and MoM growth measures; build a 1-page report with 4 visuals.",
    sessions: [
      { code: "S19", title: "Time Intelligence",
        objectives: ["Mark a proper date table", "Calculate YTD totals", "Compare to the prior year", "Build MoM and YoY growth"],
        concepts: ["Why you need a dedicated date table", "Mark as Date Table", "TOTALYTD, TOTALQTD", "SAMEPERIODLASTYEAR, DATEADD", "Month-over-Month growth", "Year-over-Year % growth with DIVIDE"],
        demo: ["Revenue YTD measure", "YoY growth % using SAMEPERIODLASTYEAR", "Trend visual by month"],
        notes: "Time intelligence needs a continuous date table — reuse calendar.csv." },
      { code: "S20", title: "Advanced DAX",
        objectives: ["Pull related columns with RELATED", "Remove/adjust filters with ALL family", "Rank with RANKX", "Use VAR for readable measures"],
        concepts: ["RELATED & RELATEDTABLE", "ALL, ALLEXCEPT, ALLSELECTED", "% of total with ALL", "RANKX for leaderboards", "DIVIDE for safe division", "VAR / RETURN for clarity & speed"],
        demo: ["% of total revenue with ALL", "Rank salespeople with RANKX", "Refactor a measure using VAR"],
        notes: "VAR both clarifies and speeds up measures — model good habits here." },
      { code: "S21", title: "Visuals & Interactivity",
        objectives: ["Choose effective core visuals", "Use advanced visuals appropriately", "Add drill-through and tooltips", "Wire up interactivity"],
        concepts: ["Cards, KPI, matrix, bar, line", "Maps, gauges, custom visuals", "Conditional formatting in visuals", "Drill-through pages & report tooltips", "Bookmarks & buttons", "Slicers, sync slicers, cross-highlighting"],
        demo: ["Build a KPI + trend + matrix page", "Add a drill-through to product detail", "Sync a slicer across pages"],
        notes: "Tie visual choice back to the dataviz principle: one clear message per visual." },
    ] },
  { n: 8, part: "PART 2 · POWER BI", theme: "Report Design, Publishing & Final Capstone", palette: PBI,
    homework: "Finish and publish the capstone report; prepare a 5-minute presentation.",
    sessions: [
      { code: "S22", title: "Report Design + Power BI Service",
        objectives: ["Apply layout & storytelling principles", "Theme a report consistently", "Add navigation and mobile layout", "Publish to the Power BI Service"],
        concepts: ["Layout, alignment & white space", "Themes (JSON) & consistent colour", "Navigation buttons & page structure", "Mobile layout view", "Publish to the Service", "Dashboards vs reports; pinning tiles"],
        demo: ["Apply a custom theme", "Publish and pin tiles to a dashboard"],
        notes: "Reference the dataviz colour and layout rules from the course notes." },
      { code: "S23", title: "Sharing, Refresh, RLS + Capstone Build",
        objectives: ["Share reports safely", "Schedule data refresh", "Explain gateways and RLS", "Start the final capstone"],
        concepts: ["Sharing & workspace permissions", "Scheduled refresh & the data gateway (concept)", "Row-Level Security (roles & rules)", "Publishing responsibly", "Capstone build time"],
        demo: ["Create a simple RLS role", "Set up scheduled refresh (walkthrough)"],
        notes: "Most of this session is supervised capstone building. Circulate and unblock." },
      { code: "S24", title: "Final Capstone Presentations + Exam",
        objectives: ["Present an end-to-end report", "Explain the model and key measures", "Answer questions confidently", "Complete the final assessment"],
        concepts: ["5-minute report presentations", "Peer & instructor feedback", "Final assessment / certification (35%)", "Next steps & further learning"],
        demo: ["Learner presentations", "Final exam"],
        notes: "Celebrate completion. Share a resource list for continued learning (DAX Guide, community)." },
    ] },
];

// ---------------------------------------------------------------- helpers
const W = 13.33, H = 7.5;

function circleNum(slide, txt, x, y, pal, d = 0.55) {
  slide.addShape("ellipse", { x, y, w: d, h: d, fill: { color: pal.primary }, line: { type: "none" } });
  slide.addText(txt, { x, y, w: d, h: d, align: "center", valign: "middle", fontFace: HEAD, bold: true, color: "FFFFFF", fontSize: 18 });
}

function titleSlide(pres, wk) {
  const p = wk.palette;
  const s = pres.addSlide();
  s.background = { color: p.dark };
  s.addShape("ellipse", { x: 10.4, y: -1.6, w: 4.6, h: 4.6, fill: { color: p.primary, transparency: 55 }, line: { type: "none" } });
  s.addShape("ellipse", { x: 11.7, y: 4.7, w: 3.4, h: 3.4, fill: { color: p.mid, transparency: 70 }, line: { type: "none" } });
  s.addText(wk.part, { x: 0.9, y: 1.4, w: 9, h: 0.5, fontFace: BODY, color: p.mid, fontSize: 16, bold: true, charSpacing: 2 });
  s.addText(`Week ${wk.n}`, { x: 0.85, y: 1.9, w: 9, h: 1.1, fontFace: HEAD, color: "FFFFFF", fontSize: 54, bold: true });
  s.addText(wk.theme, { x: 0.9, y: 3.05, w: 10.5, h: 1.2, fontFace: HEAD, color: p.light, fontSize: 30 });
  // session chips
  const chips = wk.sessions.map(x => x.code + " · " + x.title).join("     ");
  s.addText(chips, { x: 0.9, y: 5.4, w: 11.5, h: 0.8, fontFace: BODY, color: "FFFFFF", fontSize: 13, italic: true });
  s.addText("Advanced Excel & Power BI  ·  Instructor Deck  ·  3-hour sessions", { x: 0.9, y: 6.7, w: 11.5, h: 0.4, fontFace: BODY, color: p.mid, fontSize: 11 });
  s.addNotes(`Week ${wk.n}: ${wk.theme}. Three 3-hour sessions. Homework: ${wk.homework}`);
}

function roadmapSlide(pres, wk) {
  const p = wk.palette;
  const s = pres.addSlide();
  s.background = { color: "FFFFFF" };
  s.addText("This week at a glance", { x: 0.7, y: 0.5, w: 12, h: 0.8, fontFace: HEAD, color: p.dark, fontSize: 32, bold: true });
  const cardW = 3.9, gap = 0.35, startX = 0.7, y = 1.9, cardH = 4.2;
  wk.sessions.forEach((se, i) => {
    const x = startX + i * (cardW + gap);
    s.addShape("roundRect", { x, y, w: cardW, h: cardH, rectRadius: 0.1, fill: { color: p.card }, line: { color: p.light, width: 1 }, shadow: { type: "outer", blur: 6, offset: 2, angle: 90, color: "BBBBBB", opacity: 0.4 } });
    circleNum(s, String(i + 1), x + 0.3, y + 0.3, p);
    s.addText(se.code, { x: x + 1.0, y: y + 0.32, w: cardW - 1.2, h: 0.5, fontFace: BODY, color: p.primary, fontSize: 15, bold: true });
    s.addText(se.title, { x: x + 0.3, y: y + 1.0, w: cardW - 0.6, h: 1.0, fontFace: HEAD, color: p.ink, fontSize: 18, bold: true, valign: "top" });
    s.addText(se.objectives.slice(0, 3).map(o => ({ text: o, options: { bullet: { code: "2022" }, breakLine: true } })),
      { x: x + 0.3, y: y + 2.1, w: cardW - 0.55, h: cardH - 2.3, fontFace: BODY, color: p.muted, fontSize: 12, paraSpaceAfter: 6, valign: "top" });
  });
  s.addText(`Homework:  ${wk.homework}`, { x: 0.7, y: 6.5, w: 12, h: 0.6, fontFace: BODY, italic: true, color: p.primary, fontSize: 13, bold: true });
  s.addNotes("Walk through the three sessions and the week's outcome before diving in.");
}

function sessionSlide(pres, wk, se, idx) {
  const p = wk.palette;
  const s = pres.addSlide();
  s.background = { color: "FFFFFF" };
  // header band via dark title text (no accent bars)
  circleNum(s, se.code.replace("S", ""), 0.7, 0.55, p, 0.7);
  s.addText(se.code + "  ·  " + se.title, { x: 1.6, y: 0.55, w: 11, h: 0.9, fontFace: HEAD, color: p.dark, fontSize: 28, bold: true, valign: "middle" });

  const colY = 1.85, colH = 5.0;
  // left column — objectives
  s.addText("LEARNING OBJECTIVES", { x: 0.7, y: colY, w: 5.6, h: 0.4, fontFace: BODY, color: p.primary, fontSize: 14, bold: true, charSpacing: 1 });
  s.addText(se.objectives.map((o, i) => ({ text: o, options: { bullet: { code: "2713" }, breakLine: i !== se.objectives.length - 1 } })),
    { x: 0.7, y: colY + 0.45, w: 5.6, h: 2.2, fontFace: BODY, color: p.ink, fontSize: 15, paraSpaceAfter: 8, valign: "top" });
  // left column — demo
  s.addText("LIVE DEMO / BUILD", { x: 0.7, y: colY + 2.9, w: 5.6, h: 0.4, fontFace: BODY, color: p.primary, fontSize: 14, bold: true, charSpacing: 1 });
  s.addText(se.demo.map((o, i) => ({ text: o, options: { bullet: { type: "number" }, breakLine: i !== se.demo.length - 1 } })),
    { x: 0.7, y: colY + 3.35, w: 5.6, h: 1.6, fontFace: BODY, color: p.muted, fontSize: 13, paraSpaceAfter: 6, valign: "top" });

  // right column — key concepts card
  s.addShape("roundRect", { x: 6.7, y: colY, w: 5.9, h: colH, rectRadius: 0.1, fill: { color: p.card }, line: { color: p.light, width: 1 } });
  s.addText("KEY CONCEPTS TO TEACH", { x: 7.0, y: colY + 0.25, w: 5.3, h: 0.4, fontFace: BODY, color: p.dark, fontSize: 14, bold: true, charSpacing: 1 });
  s.addText(se.concepts.map((o, i) => ({ text: o, options: { bullet: { code: "2022" }, breakLine: i !== se.concepts.length - 1 } })),
    { x: 7.0, y: colY + 0.7, w: 5.35, h: colH - 0.95, fontFace: BODY, color: p.ink, fontSize: 14, paraSpaceAfter: 7, valign: "top" });

  s.addNotes(se.notes);
  return s;
}

function wrapSlide(pres, wk) {
  const p = wk.palette;
  const s = pres.addSlide();
  s.background = { color: p.dark };
  s.addText(`Week ${wk.n} wrap-up`, { x: 0.9, y: 1.0, w: 11, h: 0.9, fontFace: HEAD, color: "FFFFFF", fontSize: 36, bold: true });
  s.addText("By the end of this week learners can:", { x: 0.9, y: 2.0, w: 11, h: 0.5, fontFace: BODY, color: p.mid, fontSize: 16, bold: true });
  const allObj = wk.sessions.flatMap(se => se.objectives).slice(0, 8);
  s.addText(allObj.map((o, i) => ({ text: o, options: { bullet: { code: "2713" }, breakLine: i !== allObj.length - 1 } })),
    { x: 0.9, y: 2.6, w: 11.4, h: 3.2, fontFace: BODY, color: p.light, fontSize: 15, paraSpaceAfter: 6, valign: "top" });
  s.addText(`Homework:  ${wk.homework}`, { x: 0.9, y: 6.2, w: 11.4, h: 0.7, fontFace: BODY, italic: true, color: "FFFFFF", fontSize: 14, bold: true });
  s.addNotes("Recap objectives, assign homework, preview next week.");
}

// ---------------------------------------------------------------- build
WEEKS.forEach((wk) => {
  const pres = new pptxgen();
  pres.defineLayout({ name: "W", width: W, height: H });
  pres.layout = "W";
  pres.author = "Advanced Excel & Power BI Course";
  pres.title = `Week ${wk.n} — ${wk.theme}`;

  titleSlide(pres, wk);
  roadmapSlide(pres, wk);
  wk.sessions.forEach((se, i) => sessionSlide(pres, wk, se, i));
  wrapSlide(pres, wk);

  const partTag = wk.n <= 4 ? "Excel" : "PowerBI";
  const fn = path.join(OUT, `Week${String(wk.n).padStart(2, "0")}_${partTag}_${wk.theme.replace(/[^A-Za-z0-9]+/g, "-")}.pptx`);
  pres.writeFile({ fileName: fn }).then(() => console.log("Wrote", path.basename(fn)));
});
