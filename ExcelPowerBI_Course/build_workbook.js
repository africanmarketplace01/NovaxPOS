const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, ShadingType, PageBreak, TableOfContents,
} = require("docx");

const BLUE = "1F4E79", GREEN = "217346", GOLD = "B8860B", GREY = "F2F2F2", LIGHT = "E8F0E6";

// ---- content: 24 sessions ----
const S = [
  // WEEK 1
  { c: "S1", wk: 1, part: "Excel", t: "Excel Power Features + Tables",
    ref: ["Absolute reference $A$1 locks both row and column; F4 cycles the $ signs.",
      "Name a range: select it, type a name in the Name Box, Enter.",
      "Insert > Table (Ctrl+T) turns a range into a Table with structured references like Sales[Revenue].",
      "Tables auto-expand and auto-fill formulas when you add rows."],
    guided: { t: "Turn sales_data into a Table", steps: [
      "Open sales_data.csv and save as an .xlsx workbook.",
      "Click any cell in the data and press Ctrl+T; confirm 'My table has headers'.",
      "Rename the table to 'Sales' in Table Design > Table Name.",
      "Add a column 'LineCheck' = [@Quantity]*[@UnitPrice] and watch it fill automatically.",
      "Create a named range 'TaxRate' in an empty cell (0.075) and reference it in a formula."] },
    ind: "Convert products.csv to a Table named 'Products' and add a 'Margin' column = [@UnitPrice]-[@Cost].",
    hw: "Clean and structure the raw orders file into a proper Excel Table (carry into S2)." },
  { c: "S2", wk: 1, part: "Excel", t: "Data Cleaning Fundamentals",
    ref: ["TRIM removes extra spaces; CLEAN removes non-printing characters.",
      "PROPER/UPPER/LOWER standardise text case.",
      "Flash Fill (Ctrl+E) learns a pattern from your example.",
      "Data > Remove Duplicates deletes repeated rows; Find & Replace supports * and ? wildcards."],
    guided: { t: "Clean the messy orders file", steps: [
      "Open raw_orders_messy.csv.",
      "Use TRIM+PROPER on Customer Name to fix spacing and case.",
      "Text-to-Columns or Flash Fill to standardise the Region column.",
      "Data > Remove Duplicates on Order ID.",
      "Find & Replace '$' with nothing in the Price column, then set it to Number format."] },
    ind: "Extract the first name from each cleaned Customer Name using Flash Fill.",
    hw: "Produce a fully cleaned copy of raw_orders_messy.csv with no duplicates or stray characters." },
  { c: "S3", wk: 1, part: "Excel", t: "Logical Functions + Quiz 1",
    ref: ["IF(test, if_true, if_false).",
      "IFS(test1,val1,test2,val2,...) avoids deep nesting.",
      "AND(...)/OR(...) combine conditions; NOT() reverses one.",
      "IFERROR(formula, value_if_error) hides #N/A and #DIV/0!."],
    guided: { t: "Build a commission calculator", steps: [
      "On the Sales table, add 'Commission%' using IFS: >=10000 -> 8%, >=5000 -> 5%, else 2% (per Revenue).",
      "Add 'Commission' = Revenue * Commission%.",
      "Add 'HighValue' = IF(AND(Quantity>=5, Discount=0),\"Yes\",\"No\").",
      "Wrap a sample ratio in IFERROR to return 0 on errors."] },
    ind: "Add a 'Band' column that labels each order Gold/Silver/Bronze by Revenue using IFS.",
    hw: "Complete Quiz 1 revision; review any missed questions." },
  // WEEK 2
  { c: "S4", wk: 2, part: "Excel", t: "VLOOKUP, HLOOKUP & INDEX+MATCH",
    ref: ["VLOOKUP(value, table, col_index, FALSE) needs the key in the first column.",
      "FALSE = exact match; TRUE = approximate (sorted data only).",
      "INDEX(array, row, col) returns a value by position.",
      "MATCH(value, array, 0) returns a position; INDEX+MATCH can look left and survives column inserts."],
    guided: { t: "Price lookup two ways", steps: [
      "On sales rows, use VLOOKUP to pull Cost from the Products table by ProductID.",
      "Rebuild the same lookup with INDEX+MATCH.",
      "Insert a column inside Products and observe the VLOOKUP break while INDEX+MATCH holds.",
      "Add IFERROR around both to return 'Not found'."] },
    ind: "Use INDEX+MATCH to return a product's Category given its name.",
    hw: "Start the employee record lookup sheet (finish in S6)." },
  { c: "S5", wk: 2, part: "Excel", t: "XLOOKUP, Dynamic Arrays & References",
    ref: ["XLOOKUP(value, lookup_array, return_array, [if_not_found]) — exact match by default, looks any direction.",
      "Dynamic arrays spill: FILTER, SORT, UNIQUE, SEQUENCE.",
      "The # operator references a spill range (e.g. E2#).",
      "Dependent drop-downs: Data Validation list + INDIRECT."],
    guided: { t: "Modernise the lookup", steps: [
      "Replace an earlier VLOOKUP with XLOOKUP including an if_not_found message.",
      "Use FILTER to list all orders for one Region.",
      "Use UNIQUE + SORT to build a clean Region list.",
      "Create a Region drop-down, then a dependent Salesperson drop-down with INDIRECT."] },
    ind: "Return three columns (Product, Category, UnitPrice) in a single XLOOKUP for a chosen ProductID.",
    hw: "Finish the searchable employee lookup using XLOOKUP + IFERROR." },
  { c: "S6", wk: 2, part: "Excel", t: "Lookup Lab + Quiz 2",
    ref: ["Combine XLOOKUP, IFERROR and Data Validation into a small tool.",
      "Keep inputs on a clearly labelled control cell.",
      "Validate inputs so users can only pick valid IDs."],
    guided: { t: "Employee record finder", steps: [
      "Create an input cell with a Data Validation list of EmployeeIDs from hr_data.csv.",
      "Use XLOOKUP to return Name, Department, JobTitle and Salary for the chosen ID.",
      "Wrap each in IFERROR for a friendly message.",
      "Format the result block as a tidy 'card'."] },
    ind: "Add a second finder that searches by Department and returns a FILTER of all matching staff.",
    hw: "Complete Quiz 2 revision." },
  // WEEK 3
  { c: "S7", wk: 3, part: "Excel", t: "PivotTables",
    ref: ["Build from a Table: Insert > PivotTable.",
      "Drag fields into Rows / Columns / Values / Filters.",
      "Value Field Settings: Show Values As % of Grand Total, Running Total.",
      "Add Slicers (point-and-click filters) and a Timeline for dates."],
    guided: { t: "Summarise sales", steps: [
      "Create a PivotTable of Revenue by Region (rows) and Category (columns).",
      "Change Revenue to 'Show Values As % of Grand Total'.",
      "Group OrderDate by Month and Year.",
      "Insert a Region Slicer and an OrderDate Timeline."] },
    ind: "Build a PivotTable of average Revenue per Salesperson, sorted descending.",
    hw: "Draft the layout for your one-page dashboard (due S9)." },
  { c: "S8", wk: 3, part: "Excel", t: "Charts & Conditional Formatting",
    ref: ["Match chart to message: trend -> line, comparison -> bar/column, part-to-whole -> stacked.",
      "Combo chart plots two measures on primary/secondary axes.",
      "Conditional formatting: data bars, colour scales, icon sets, formula rules.",
      "Sparklines show an in-cell trend."],
    guided: { t: "Visualise performance", steps: [
      "Build a combo chart: Revenue as columns, Margin% as a line on a secondary axis.",
      "Apply data bars to a Revenue column.",
      "Add a formula rule to highlight rows where Revenue < 500.",
      "Insert sparklines showing monthly revenue per salesperson."] },
    ind: "Create an icon-set rule that flags Margin% as green/amber/red by threshold.",
    hw: "Add at least two charts to your dashboard draft." },
  { c: "S9", wk: 3, part: "Excel", t: "Interactive Dashboard Build",
    ref: ["Plan the layout before building (title, KPIs, charts, filters).",
      "Report Connections links one Slicer to many PivotTables.",
      "KPI cells: single-number summaries (Total Revenue, Top Region).",
      "Polish: consistent colours, number formats, hidden gridlines."],
    guided: { t: "Assemble the dashboard", steps: [
      "Place 3 KPI cells across the top (Total Revenue, Total Margin, Top Region).",
      "Add two PivotCharts (by Region, by Month).",
      "Insert one Slicer and use Report Connections to control both charts.",
      "Hide gridlines, freeze the title, apply a consistent colour theme."] },
    ind: "Add a Category Slicer and confirm every visual reacts to it.",
    hw: "Finalise your one-page regional sales dashboard." },
  // WEEK 4
  { c: "S10", wk: 4, part: "Excel", t: "Text, Date & Statistical Functions",
    ref: ["TEXTJOIN(delim, ignore_empty, ...) concatenates with a separator.",
      "DATEDIF(start, end, \"Y\") returns whole years; EOMONTH, NETWORKDAYS for date math.",
      "SUMIFS/COUNTIFS/AVERAGEIFS aggregate by multiple conditions.",
      "SUBTOTAL respects filters; AGGREGATE can ignore errors/hidden rows; RANK ranks values."],
    guided: { t: "Function toolkit", steps: [
      "Build a full label with TEXTJOIN from several columns.",
      "Compute employee tenure with DATEDIF on hr_data.csv HireDate.",
      "Total Revenue for 'North' + 'Electronics' with SUMIFS.",
      "Rank salespeople by total Revenue with RANK/RANK.EQ."] },
    ind: "Use COUNTIFS to count orders per Region with Discount > 0.",
    hw: "Install Power BI Desktop before Week 5." },
  { c: "S11", wk: 4, part: "Excel", t: "Power Query, What-If & Macros",
    ref: ["Data > Get & Transform to import and clean; Applied Steps record every action.",
      "Merge = join two queries on a key; Append = stack rows.",
      "Goal Seek finds an input for a target output; Scenario Manager stores input sets.",
      "Record a macro to automate a repetitive task."],
    guided: { t: "Query and model", steps: [
      "Import raw_orders_messy.csv into Power Query and clean it (types, trim, remove errors).",
      "Merge the result with Products on ProductID to add Cost.",
      "Close & Load to a Table.",
      "Use Goal Seek to find the discount that yields a target total."] },
    ind: "Append two monthly extracts into one query (simulate by duplicating and filtering by year).",
    hw: "Review the Excel capstone brief (below) and gather your cleaned data." },
  { c: "S12", wk: 4, part: "Excel", t: "Excel Capstone + Mid-Course Assessment",
    ref: ["Capstone = clean -> enrich -> analyse -> visualise, independently.",
      "Deliverable: a one-page dashboard plus the cleaned data behind it.",
      "You will be graded against the rubric in the appendix.",
      "Leave time for the written mid-course assessment."],
    guided: { t: "Capstone kickoff", steps: [
      "Clean raw_orders_messy.csv with Power Query.",
      "Enrich with product Margin using XLOOKUP from products.csv.",
      "Build a PivotTable + Slicer + combo chart + 3 KPI cells.",
      "Save and be ready to present."] },
    ind: "Complete the capstone dashboard end-to-end (graded, 25%).",
    hw: "None — prepare for Power BI." },
  // WEEK 5
  { c: "S13", wk: 5, part: "Power BI", t: "Power BI Overview + Getting Data",
    ref: ["Power BI Desktop (author) -> Service (share) -> Mobile (view).",
      "Three views: Report, Data (Table), Model.",
      "Get Data supports Excel, CSV, Web, Folder and many more.",
      "Choose Transform Data to clean before loading."],
    guided: { t: "First connection", steps: [
      "Install and open Power BI Desktop; tour the three views.",
      "Get Data > Text/CSV and load sales_data.csv, products.csv, calendar.csv.",
      "Choose Transform Data on at least one to open Power Query.",
      "Save as SalesModel.pbix."] },
    ind: "Connect to hr_data.csv and preview it in the Data view.",
    hw: "Load the full dataset folder and save your .pbix." },
  { c: "S14", wk: 5, part: "Power BI", t: "Power Query in Power BI",
    ref: ["Same engine as Excel Get & Transform.",
      "Set data types explicitly (dates, currency, whole number).",
      "Applied Steps can be edited, reordered or deleted.",
      "Reference reuses a query's output; Duplicate copies its steps."],
    guided: { t: "Shape the tables", steps: [
      "In Power Query, promote headers and set correct data types.",
      "Remove null/blank rows and unneeded columns.",
      "Reorder an applied step and observe the effect.",
      "Close & Apply."] },
    ind: "Create a Reference of the Sales query filtered to a single year.",
    hw: "Ensure all tables have clean types before Week 6." },
  { c: "S15", wk: 5, part: "Power BI", t: "Shaping Data + Lab",
    ref: ["Merge queries = join on a key (left/inner/etc.).",
      "Append queries = stack tables with the same columns.",
      "Unpivot turns wide columns into rows; Split column by delimiter.",
      "Combine files loads every file in a folder."],
    guided: { t: "Prepare the model tables", steps: [
      "Merge Sales with Products on ProductID to bring in Cost.",
      "Confirm the calendar table is continuous.",
      "Split or unpivot a sample wide table.",
      "Close & Apply and save."] },
    ind: "Append a duplicated Sales query to itself filtered by two years, then remove duplicates.",
    hw: "Save the cleaned .pbix — it becomes the Week 6 model." },
  // WEEK 6
  { c: "S16", wk: 6, part: "Power BI", t: "Data Modeling & Star Schema",
    ref: ["Fact table = events/measures (Sales). Dimension tables = descriptive (Products, Calendar).",
      "Star schema: one fact linked to several dimensions.",
      "Relationships have cardinality (1-to-many) and a filter direction.",
      "Only one active relationship between two tables at a time."],
    guided: { t: "Build the star", steps: [
      "In Model view, create Sales[ProductID] -> Products[ProductID].",
      "Create Sales[OrderDate] -> Calendar[Date].",
      "Check cardinality is Many-to-One and single filter direction.",
      "Hide key columns you don't need in visuals."] },
    ind: "Add a relationship from Sales[Region] to a new Region dimension (create one via Enter Data).",
    hw: "Confirm the model diagram looks like a star (no loops)." },
  { c: "S17", wk: 6, part: "Power BI", t: "DAX Basics",
    ref: ["Measure = calculated at query time (preferred); calculated column = stored per row.",
      "SUM, AVERAGE, COUNT, DISTINCTCOUNT.",
      "CALCULATE(expr, filters) changes the filter context — the key DAX function.",
      "Row context (per row) vs filter context (from slicers/visuals)."],
    guided: { t: "First measures", steps: [
      "Total Revenue = SUM(Sales[Revenue]).",
      "Total Cost = SUMX(Sales, Sales[Quantity]*RELATED(Products[Cost])).",
      "Electronics Revenue = CALCULATE([Total Revenue], Products[Category]=\"Electronics\").",
      "Format measures as currency."] },
    ind: "Write a Margin measure = [Total Revenue] - [Total Cost] and a Margin % with DIVIDE.",
    hw: "Prepare 5 measures for the S18 lab." },
  { c: "S18", wk: 6, part: "Power BI", t: "DAX Practice Lab + Quiz",
    ref: ["Reusable measures keep reports consistent.",
      "Test measures in a matrix visual before building the report.",
      "DIVIDE avoids divide-by-zero errors.",
      "Format each measure appropriately."],
    guided: { t: "Author the core measures", steps: [
      "Create: Total Revenue, Total Cost, Margin, Margin %, Avg Order Value.",
      "Drop them into a matrix by Category to verify.",
      "Add Orders = DISTINCTCOUNT(Sales[OrderID]).",
      "Add Units Sold = SUM(Sales[Quantity])."] },
    ind: "Build a matrix of all measures by Region and screenshot it.",
    hw: "Complete the DAX quiz revision." },
  // WEEK 7
  { c: "S19", wk: 7, part: "Power BI", t: "Time Intelligence",
    ref: ["Mark the calendar table as a Date Table.",
      "TOTALYTD([measure], Calendar[Date]) for year-to-date.",
      "SAMEPERIODLASTYEAR and DATEADD shift the date context.",
      "YoY % = DIVIDE(This - LastYear, LastYear)."],
    guided: { t: "Add time measures", steps: [
      "Mark Calendar as the Date Table.",
      "Revenue YTD = TOTALYTD([Total Revenue], Calendar[Date]).",
      "Revenue LY = CALCULATE([Total Revenue], SAMEPERIODLASTYEAR(Calendar[Date])).",
      "YoY % = DIVIDE([Total Revenue]-[Revenue LY], [Revenue LY])."] },
    ind: "Add a Month-over-Month growth measure using DATEADD by -1 month.",
    hw: "Add YoY and MoM to a trend chart." },
  { c: "S20", wk: 7, part: "Power BI", t: "Advanced DAX",
    ref: ["RELATED pulls a column across a relationship.",
      "ALL/ALLEXCEPT remove filters (for % of total).",
      "RANKX ranks items within a table.",
      "VAR/RETURN makes measures readable and faster."],
    guided: { t: "Sharpen measures", steps: [
      "% of Total = DIVIDE([Total Revenue], CALCULATE([Total Revenue], ALL(Products))).",
      "Salesperson Rank = RANKX(ALL(Sales[Salesperson]), [Total Revenue]).",
      "Refactor a measure using VAR/RETURN.",
      "Compare performance/readability before and after."] },
    ind: "Build a Top-N flag measure that marks the top 3 salespeople.",
    hw: "Add a ranked leaderboard visual to your report." },
  { c: "S21", wk: 7, part: "Power BI", t: "Visuals & Interactivity",
    ref: ["Cards/KPI for headline numbers; matrix for detail; line for trend.",
      "Drill-through jumps to a detail page filtered by the clicked item.",
      "Bookmarks capture a view; buttons trigger navigation.",
      "Slicers, sync slicers and cross-highlighting connect visuals."],
    guided: { t: "Build an interactive page", steps: [
      "Create KPI cards, a trend line, and a matrix by Category.",
      "Add a Product detail page and set up drill-through by Product.",
      "Add a Region slicer and sync it across pages.",
      "Add a bookmark + button to reset filters."] },
    ind: "Add a report tooltip page showing revenue trend for the hovered category.",
    hw: "Assemble a 1-page report with at least 4 visuals." },
  // WEEK 8
  { c: "S22", wk: 8, part: "Power BI", t: "Report Design + Service",
    ref: ["Design for one message per page; align to a grid.",
      "Themes give consistent colour; save/apply a theme JSON.",
      "Navigation buttons + a Mobile layout improve usability.",
      "Publish to the Service; dashboards pin tiles from reports."],
    guided: { t: "Polish and publish", steps: [
      "Apply a consistent theme and tidy the layout.",
      "Add navigation buttons and set a Mobile layout.",
      "Publish the report to your Power BI Service workspace.",
      "Pin two visuals to a new dashboard."] },
    ind: "Create a second report page (detail) and link to it with a button.",
    hw: "Continue building the final capstone." },
  { c: "S23", wk: 8, part: "Power BI", t: "Sharing, Refresh, RLS + Capstone",
    ref: ["Share via workspace roles/permissions.",
      "Scheduled refresh keeps data current (needs a gateway for local sources).",
      "Row-Level Security restricts rows by role.",
      "Most of this session is supervised capstone build time."],
    guided: { t: "Secure and refresh", steps: [
      "Create an RLS role filtering Sales[Region] = \"North\".",
      "Test the role with View As.",
      "Walk through setting up scheduled refresh.",
      "Continue the capstone."] },
    ind: "Add a second RLS role for a different region and test it.",
    hw: "Finish and publish the capstone; prepare a 5-minute presentation." },
  { c: "S24", wk: 8, part: "Power BI", t: "Final Capstone Presentations + Exam",
    ref: ["Present the story: business question -> data -> model -> insight.",
      "Explain your key measures and one design choice.",
      "Expect Q&A from peers and instructor.",
      "Complete the final written assessment."],
    guided: { t: "Present", steps: [
      "Give a 5-minute walkthrough of your published report.",
      "Explain the star schema and two measures.",
      "Take questions.",
      "Sit the final assessment (35%)."] },
    ind: "Peer-review one classmate's report against the rubric.",
    hw: "None — course complete. Keep practising with new datasets!" },
];

// ---- helpers ----
const partColor = (p) => (p === "Excel" ? GREEN : GOLD);
const P = (t, o = {}) => new Paragraph({ children: [new TextRun({ text: t, ...o })], spacing: { after: 80 } });
const numbered = (items) => items.map((t, i) => new Paragraph({ children: [new TextRun({ text: `${i + 1}.  `, bold: true }), new TextRun({ text: t })], spacing: { after: 50 }, indent: { left: 360, hanging: 220 } }));
const bullets = (items) => items.map((t) => new Paragraph({ text: t, bullet: { level: 0 }, spacing: { after: 40 } }));

function labelBox(label, color) {
  return new Paragraph({ spacing: { before: 120, after: 60 }, children: [new TextRun({ text: label, bold: true, color: "FFFFFF", size: 19 })], shading: { type: ShadingType.CLEAR, color: "auto", fill: color }, border: {} });
}

const children = [];
// title
children.push(
  new Paragraph({ text: "", spacing: { before: 2000 } }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "Advanced Excel & Power BI", bold: true, size: 52, color: BLUE })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 120 }, children: [new TextRun({ text: "Student Workbook", size: 40, color: "404040" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 200 }, children: [new TextRun({ text: "24 sessions of exercises, references & homework  ·  8 weeks", size: 20, italics: true, color: "606060" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 600 }, children: [new TextRun({ text: "Name: ______________________________", size: 22 })] }),
  new Paragraph({ children: [new PageBreak()] }),
);
// TOC
children.push(new Paragraph({ text: "Contents", heading: HeadingLevel.HEADING_1 }));
children.push(new TableOfContents("Contents", { hyperlink: true, headingStyleRange: "1-1" }));
children.push(new Paragraph({ children: [new PageBreak()] }));

// how to use
children.push(new Paragraph({ text: "How to use this workbook", heading: HeadingLevel.HEADING_1 }));
children.push(P("Each session has four parts: a Quick Reference to skim before class, a Guided Exercise you build with your instructor, an Independent Challenge to try on your own, and Homework. All exercises use the practice datasets provided with the course (see the Datasets appendix)."));
children.push(new Paragraph({ children: [new PageBreak()] }));

let curWeek = 0;
S.forEach((s) => {
  if (s.wk !== curWeek) {
    curWeek = s.wk;
    // no separate page; heading carries week
  }
  children.push(new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 200, after: 60 },
    children: [
      new TextRun({ text: `${s.c}  `, bold: true, color: partColor(s.part), size: 30 }),
      new TextRun({ text: s.t, bold: true, color: BLUE, size: 30 }),
    ],
  }));
  children.push(new Paragraph({ spacing: { after: 60 }, children: [new TextRun({ text: `Week ${s.wk} · ${s.part}`, italics: true, color: "808080", size: 18 })] }));

  children.push(labelBox("QUICK REFERENCE", partColor(s.part)));
  bullets(s.ref).forEach((p) => children.push(p));

  children.push(labelBox(`GUIDED EXERCISE — ${s.guided.t}`, partColor(s.part)));
  numbered(s.guided.steps).forEach((p) => children.push(p));

  children.push(labelBox("INDEPENDENT CHALLENGE", partColor(s.part)));
  children.push(P(s.ind));

  children.push(labelBox("HOMEWORK", partColor(s.part)));
  children.push(P(s.hw));

  children.push(new Paragraph({ children: [new PageBreak()] }));
});

// Appendix: datasets
children.push(new Paragraph({ text: "Appendix A — Practice Datasets", heading: HeadingLevel.HEADING_1 }));
[
  "sales_data.csv (1,500 rows) — main fact table: orders with region, salesperson, product, quantity, discount, revenue.",
  "products.csv (15) — product, category, unit price, cost.",
  "calendar.csv (731) — date dimension 2024-2025 for time intelligence.",
  "hr_data.csv (120) — employees with department, title, hire date, salary.",
  "finance_data.csv (800) — income/expense ledger by department and category.",
  "raw_orders_messy.csv (~215) — deliberately dirty data for cleaning practice.",
].forEach((t) => children.push(new Paragraph({ text: t, bullet: { level: 0 }, spacing: { after: 50 } })));

// Appendix: capstone rubrics
children.push(new Paragraph({ text: "Appendix B — Capstone Briefs & Rubric", heading: HeadingLevel.HEADING_1, spacing: { before: 200 } }));
children.push(new Paragraph({ text: "Excel Capstone (S12, 25%)", heading: HeadingLevel.HEADING_2 }));
children.push(P("Clean raw_orders_messy.csv with Power Query, enrich it with product margins via XLOOKUP from products.csv, then build a one-page dashboard containing a PivotTable, a Slicer, a combo chart and three KPI cells (Total Revenue, Total Margin, Top Region)."));
children.push(new Paragraph({ text: "Power BI Capstone (S23-S24, 35%)", heading: HeadingLevel.HEADING_2 }));
children.push(P("Build a star schema from sales_data, products and calendar. Create measures for Total Revenue, Margin %, YoY Growth and Revenue per Salesperson. Design a two-page report (executive summary + drill-through detail), publish it to the Power BI Service, and present for five minutes."));

// rubric table
const rubric = [
  ["Data preparation / cleaning", "20"],
  ["Correct formulas / model & measures", "30"],
  ["Visualisation & dashboard design", "25"],
  ["Interactivity (slicers/drill-through)", "15"],
  ["Presentation & explanation", "10"],
];
const w = [7000, 2360];
children.push(new Paragraph({ text: "", spacing: { after: 80 } }));
children.push(new Table({
  columnWidths: w, width: { size: 9360, type: WidthType.DXA },
  rows: [
    new TableRow({ tableHeader: true, children: [
      new TableCell({ width: { size: w[0], type: WidthType.DXA }, shading: { type: ShadingType.CLEAR, color: "auto", fill: BLUE }, margins: { top: 60, bottom: 60, left: 100, right: 100 }, children: [new Paragraph({ children: [new TextRun({ text: "Criterion", bold: true, color: "FFFFFF" })] })] }),
      new TableCell({ width: { size: w[1], type: WidthType.DXA }, shading: { type: ShadingType.CLEAR, color: "auto", fill: BLUE }, margins: { top: 60, bottom: 60, left: 100, right: 100 }, children: [new Paragraph({ children: [new TextRun({ text: "Marks", bold: true, color: "FFFFFF" })] })] }),
    ] }),
    ...rubric.map((r, i) => new TableRow({ children: [
      new TableCell({ width: { size: w[0], type: WidthType.DXA }, shading: { type: ShadingType.CLEAR, color: "auto", fill: i % 2 ? GREY : "FFFFFF" }, margins: { top: 60, bottom: 60, left: 100, right: 100 }, children: [new Paragraph(r[0])] }),
      new TableCell({ width: { size: w[1], type: WidthType.DXA }, shading: { type: ShadingType.CLEAR, color: "auto", fill: i % 2 ? GREY : "FFFFFF" }, margins: { top: 60, bottom: 60, left: 100, right: 100 }, children: [new Paragraph({ children: [new TextRun({ text: r[1], bold: true })] })] }),
    ] })),
  ],
}));

const doc = new Document({
  creator: "Course",
  title: "Advanced Excel & Power BI — Student Workbook",
  features: { updateFields: true },
  styles: {
    default: { document: { run: { font: "Calibri", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true, run: { size: 30, bold: true, color: BLUE }, paragraph: { spacing: { before: 240, after: 120 } } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true, run: { size: 24, bold: true, color: "2E5496" }, paragraph: { spacing: { before: 160, after: 60 } } },
    ],
  },
  sections: [{ properties: { page: { size: { width: 12240, height: 15840 }, margin: { top: 1080, bottom: 1080, left: 1080, right: 1080 } } }, children }],
});
Packer.toBuffer(doc).then((buf) => {
  const out = path.join(__dirname, "Student_Workbook_Excel_and_PowerBI.docx");
  fs.writeFileSync(out, buf);
  console.log("Wrote", path.basename(out), buf.length, "bytes");
});
