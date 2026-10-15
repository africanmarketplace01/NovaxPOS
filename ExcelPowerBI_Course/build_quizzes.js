const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  PageBreak, TableOfContents, ShadingType,
} = require("docx");

const BLUE = "1F4E79", GREEN = "217346", GOLD = "B8860B";

// m = multiple choice (options + index of answer), s = short answer (answer text)
const QUIZZES = [
  { n: 1, week: 1, part: "Excel", topic: "Foundations, Cleaning & Logic", q: [
    { t: "m", q: "Which reference stays fixed on both row and column when copied?", o: ["A1", "$A1", "A$1", "$A$1"], a: 3 },
    { t: "m", q: "The keyboard shortcut to create a Table from a range is:", o: ["Ctrl+L only", "Ctrl+T", "Ctrl+Shift+T", "Alt+T"], a: 1 },
    { t: "m", q: "Which function removes extra spaces from text?", o: ["CLEAN", "TRIM", "PROPER", "SUBSTITUTE"], a: 1 },
    { t: "m", q: "Flash Fill is triggered with:", o: ["Ctrl+E", "Ctrl+F", "Ctrl+Enter", "F5"], a: 0 },
    { t: "m", q: "To hide a #DIV/0! error you would wrap the formula in:", o: ["ISERROR", "IFERROR", "IFNA", "ERROR.TYPE"], a: 1 },
    { t: "m", q: "Which is the best choice for testing several bands without deep nesting?", o: ["Nested IF", "IFS", "AND", "CHOOSE"], a: 1 },
    { t: "s", q: "Write a formula that returns \"Yes\" if B2 is at least 5 AND C2 equals 0, otherwise \"No\".", a: '=IF(AND(B2>=5, C2=0), "Yes", "No")' },
    { t: "s", q: "Name one benefit of converting a range into an Excel Table.", a: "Any one of: formulas auto-fill down, the range auto-expands with new rows, structured references, easy filtering/styling, works well with PivotTables/Power BI." },
    { t: "s", q: "Which feature quickly deletes repeated rows based on selected columns?", a: "Data > Remove Duplicates." },
    { t: "s", q: "What does PROPER(\"john  DOE\") return (ignoring the double space)?", a: '"John Doe" (each word capitalised).' },
  ] },
  { n: 2, week: 2, part: "Excel", topic: "Lookup & Reference Functions", q: [
    { t: "m", q: "In VLOOKUP, the lookup value must be located in the table's:", o: ["Last column", "First column", "Any column", "Header row"], a: 1 },
    { t: "m", q: "Which last argument gives an EXACT match in VLOOKUP?", o: ["TRUE", "1", "FALSE", "blank"], a: 2 },
    { t: "m", q: "A key advantage of INDEX+MATCH over VLOOKUP is that it:", o: ["Is always faster", "Can look to the LEFT of the key", "Needs sorted data", "Ignores errors"], a: 1 },
    { t: "m", q: "XLOOKUP's default match type is:", o: ["Approximate", "Exact", "Wildcard", "Next larger"], a: 1 },
    { t: "m", q: "Which function returns a spilled list of unique values?", o: ["SORT", "UNIQUE", "FILTER", "DISTINCT"], a: 1 },
    { t: "m", q: "Dependent drop-down lists are commonly built with Data Validation and:", o: ["OFFSET only", "INDIRECT", "VLOOKUP", "CHOOSE"], a: 1 },
    { t: "s", q: "Write an XLOOKUP that returns the price from Prices given an ID in A2, keys in F:F and prices in G:G, returning \"Not found\" if missing.", a: '=XLOOKUP(A2, F:F, G:G, "Not found")' },
    { t: "s", q: "Write INDEX+MATCH to return the value in column C of the row where column A equals A2.", a: "=INDEX(C:C, MATCH(A2, A:A, 0))" },
    { t: "s", q: "Why might a VLOOKUP break when someone inserts a column into the source table?", a: "The hard-coded col_index_num now points to the wrong column, since it counts columns by position." },
    { t: "s", q: "What does the # symbol refer to when placed after a cell like E2#?", a: "The entire spill range that begins in E2 (a dynamic array)." },
  ] },
  { n: 3, week: 3, part: "Excel", topic: "PivotTables, Charts & Dashboards", q: [
    { t: "m", q: "Which zone of a PivotTable turns a field into the numbers being summarised?", o: ["Rows", "Columns", "Values", "Filters"], a: 2 },
    { t: "m", q: "To show each region as a percentage of the total, use:", o: ["Sort Largest to Smallest", "Show Values As > % of Grand Total", "Group", "Subtotal"], a: 1 },
    { t: "m", q: "A Slicer is best described as:", o: ["A chart type", "A visual filter you click", "A formula", "A macro"], a: 1 },
    { t: "m", q: "Which chart best shows a trend over time?", o: ["Pie", "Line", "Doughnut", "Radar"], a: 1 },
    { t: "m", q: "A combo chart is useful when you need to:", o: ["Show one series", "Plot two measures on different scales", "Replace a PivotTable", "Add slicers"], a: 1 },
    { t: "m", q: "Which conditional formatting shows a mini bar inside each cell?", o: ["Colour scales", "Icon sets", "Data bars", "Sparklines"], a: 2 },
    { t: "s", q: "How do you make one Slicer control several PivotTables at once?", a: "Right-click the Slicer > Report Connections (PivotTable Connections) and tick the PivotTables to link." },
    { t: "s", q: "Name the four field zones of a PivotTable.", a: "Rows, Columns, Values, Filters." },
    { t: "s", q: "What must you do after the source data changes for the PivotTable to update?", a: "Refresh the PivotTable (right-click > Refresh, or Data > Refresh All)." },
    { t: "s", q: "Give one design tip for a clear one-page dashboard.", a: "Any one: consistent colours/number formats, one message per chart, KPIs at the top, remove gridlines, align elements to a grid." },
  ] },
  { n: 4, week: 4, part: "Excel", topic: "Functions, Power Query & What-If", q: [
    { t: "m", q: "Which function joins text with a chosen delimiter and can ignore blanks?", o: ["CONCAT", "TEXTJOIN", "JOIN", "MERGE"], a: 1 },
    { t: "m", q: "To count rows meeting multiple conditions, use:", o: ["COUNT", "COUNTIF", "COUNTIFS", "DCOUNT"], a: 2 },
    { t: "m", q: "DATEDIF(start, end, \"Y\") returns:", o: ["Days between", "Whole years between", "Months between", "Weekdays"], a: 1 },
    { t: "m", q: "In Power Query, joining two queries on a key is called:", o: ["Append", "Merge", "Pivot", "Group By"], a: 1 },
    { t: "m", q: "Stacking two tables with the same columns is called:", o: ["Merge", "Append", "Unpivot", "Split"], a: 1 },
    { t: "m", q: "Which tool finds the input needed to reach a target formula result?", o: ["Scenario Manager", "Goal Seek", "Data Table", "Solver add-in"], a: 1 },
    { t: "s", q: "Write a SUMIFS that totals Revenue (col D) where Region (col B) = \"North\" and Category (col C) = \"Electronics\".", a: '=SUMIFS(D:D, B:B, "North", C:C, "Electronics")' },
    { t: "s", q: "What is the list of recorded actions in Power Query called?", a: "Applied Steps." },
    { t: "s", q: "Which function respects filtered/hidden rows when summing a visible list?", a: "SUBTOTAL (or AGGREGATE)." },
    { t: "s", q: "Name one difference between Merge and Append in Power Query.", a: "Merge joins columns from two queries on a key (side-by-side); Append stacks rows of similarly-structured tables (top-to-bottom)." },
  ] },
  { n: 5, week: 5, part: "Power BI", topic: "Power BI Foundations & Data Prep", q: [
    { t: "m", q: "Which Power BI product is used to AUTHOR reports on Windows?", o: ["Power BI Service", "Power BI Desktop", "Power BI Mobile", "Excel"], a: 1 },
    { t: "m", q: "Which is NOT one of Power BI Desktop's three views?", o: ["Report", "Data", "Model", "Macro"], a: 3 },
    { t: "m", q: "To load every file in a folder at once, use Get Data:", o: ["Web", "Folder", "CSV", "SQL"], a: 1 },
    { t: "m", q: "The Power Query engine in Power BI is essentially the same as Excel's:", o: ["Solver", "Get & Transform", "Analysis ToolPak", "Power Pivot"], a: 1 },
    { t: "m", q: "Choosing 'Transform Data' instead of 'Load' lets you:", o: ["Publish faster", "Clean data before loading", "Skip the model", "Create measures"], a: 1 },
    { t: "m", q: "The difference between Reference and Duplicate of a query is:", o: ["No difference", "Reference points to the query's output; Duplicate copies its steps", "Duplicate is live-linked", "Reference deletes the source"], a: 1 },
    { t: "s", q: "What file extension does a Power BI Desktop file use?", a: ".pbix" },
    { t: "s", q: "Why should you set correct data types in Power Query before loading?", a: "So aggregations, dates/time intelligence, relationships and visuals behave correctly (e.g., dates recognised, numbers summable)." },
    { t: "s", q: "Name one Power Query transformation for turning wide columns into rows.", a: "Unpivot (Columns)." },
    { t: "s", q: "What command in Power Query applies your steps and returns to the report?", a: "Close & Apply." },
  ] },
  { n: 6, week: 6, part: "Power BI", topic: "Data Modeling & DAX Basics", q: [
    { t: "m", q: "In a star schema, the central table of events/measures is the:", o: ["Dimension table", "Fact table", "Lookup table", "Bridge table"], a: 1 },
    { t: "m", q: "A typical sales relationship cardinality is:", o: ["One-to-one", "Many-to-one", "Many-to-many", "None"], a: 1 },
    { t: "m", q: "Which is generally preferred for calculations that respond to filters?", o: ["Calculated column", "Measure", "Custom column", "Query parameter"], a: 1 },
    { t: "m", q: "The single most important DAX function for changing filter context is:", o: ["SUM", "CALCULATE", "RELATED", "FILTER"], a: 1 },
    { t: "m", q: "To count unique order IDs you use:", o: ["COUNT", "COUNTA", "DISTINCTCOUNT", "COUNTROWS"], a: 2 },
    { t: "m", q: "SUMX is an example of a function that iterates using:", o: ["Filter context only", "Row context", "No context", "Slicer context"], a: 1 },
    { t: "s", q: "Write a measure for Total Revenue summing the Revenue column of the Sales table.", a: "Total Revenue = SUM(Sales[Revenue])" },
    { t: "s", q: "Write a Margin % measure using DIVIDE from existing [Total Revenue] and [Total Cost] measures.", a: "Margin % = DIVIDE([Total Revenue]-[Total Cost], [Total Revenue])" },
    { t: "s", q: "In one sentence, contrast row context and filter context.", a: "Row context evaluates a formula for the current row; filter context is the set of filters (from slicers, visuals, CALCULATE) applied to the data before the calculation." },
    { t: "s", q: "Why is DIVIDE preferred over the / operator in DAX?", a: "DIVIDE safely handles division by zero (returns blank or a specified alternate) instead of an error." },
  ] },
  { n: 7, week: 7, part: "Power BI", topic: "Time Intelligence, Advanced DAX & Visuals", q: [
    { t: "m", q: "Time-intelligence functions require a:", o: ["Fact table", "Marked Date table", "Measure table", "Slicer"], a: 1 },
    { t: "m", q: "Which returns the same period one year earlier?", o: ["DATEADD", "SAMEPERIODLASTYEAR", "PARALLELPERIOD", "PREVIOUSYEAR"], a: 1 },
    { t: "m", q: "To compute a % of total ignoring the category filter, wrap the total in:", o: ["ALL", "KEEPFILTERS", "VALUES", "EARLIER"], a: 0 },
    { t: "m", q: "Which function ranks items within a table?", o: ["TOPN", "RANKX", "RANK.EQ", "ORDERBY"], a: 1 },
    { t: "m", q: "VAR in a measure primarily improves:", o: ["File size", "Readability and performance", "Colour", "Security"], a: 1 },
    { t: "m", q: "Jumping from a summary visual to a filtered detail page uses:", o: ["Bookmark", "Drill-through", "Tooltip", "Slicer"], a: 1 },
    { t: "s", q: "Write a year-to-date revenue measure using TOTALYTD and a Calendar[Date] column.", a: "Revenue YTD = TOTALYTD([Total Revenue], Calendar[Date])" },
    { t: "s", q: "Write a YoY % measure given [Total Revenue] and [Revenue LY].", a: "YoY % = DIVIDE([Total Revenue]-[Revenue LY], [Revenue LY])" },
    { t: "s", q: "Name one Power BI feature that lets a user reset or switch to a saved view.", a: "Bookmarks (often paired with buttons)." },
    { t: "s", q: "What visual element captures headline single numbers like Total Revenue?", a: "Card (or KPI) visual." },
  ] },
];

const LETTERS = ["A", "B", "C", "D"];
const partColor = (p) => (p === "Excel" ? GREEN : GOLD);

const children = [];
// title
children.push(
  new Paragraph({ text: "", spacing: { before: 2000 } }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "Advanced Excel & Power BI", bold: true, size: 52, color: BLUE })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 120 }, children: [new TextRun({ text: "Quiz Bank & Answer Keys", size: 40, color: "404040" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 200 }, children: [new TextRun({ text: "7 weekly quizzes · 10 questions each · with full answer key", size: 20, italics: true, color: "606060" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 400 }, children: [new TextRun({ text: "Instructor copy — remove the Answer Key section before handing out.", size: 18, italics: true, color: "C00000" })] }),
  new Paragraph({ children: [new PageBreak()] }),
);
children.push(new Paragraph({ text: "Contents", heading: HeadingLevel.HEADING_1 }));
children.push(new TableOfContents("Contents", { hyperlink: true, headingStyleRange: "1-1" }));
children.push(new Paragraph({ children: [new PageBreak()] }));

// ---- Quizzes (questions only) ----
children.push(new Paragraph({ text: "Part 1 — Quizzes", heading: HeadingLevel.HEADING_1 }));
children.push(new Paragraph({ spacing: { after: 120 }, children: [new TextRun({ text: "Each quiz is worth 10 marks (1 per question). Recommended time: 15 minutes.", italics: true, color: "606060" })] }));

QUIZZES.forEach((qz) => {
  children.push(new Paragraph({
    heading: HeadingLevel.HEADING_1, spacing: { before: 200, after: 40 },
    children: [
      new TextRun({ text: `Quiz ${qz.n}  `, bold: true, color: partColor(qz.part), size: 30 }),
      new TextRun({ text: `— ${qz.topic}`, bold: true, color: BLUE, size: 30 }),
    ],
  }));
  children.push(new Paragraph({ spacing: { after: 80 }, children: [
    new TextRun({ text: `Week ${qz.week} · ${qz.part}      `, italics: true, color: "808080", size: 18 }),
    new TextRun({ text: "Name: __________________   Score: ____ / 10", size: 18, color: "808080" }),
  ] }));
  qz.q.forEach((item, i) => {
    children.push(new Paragraph({
      spacing: { before: 80, after: 40 },
      children: [new TextRun({ text: `${i + 1}. `, bold: true }), new TextRun({ text: item.q })],
    }));
    if (item.t === "m") {
      item.o.forEach((opt, j) => children.push(new Paragraph({
        indent: { left: 520 }, spacing: { after: 20 },
        children: [new TextRun({ text: `${LETTERS[j]})  `, bold: true }), new TextRun({ text: opt })],
      })));
    } else {
      children.push(new Paragraph({ indent: { left: 520 }, spacing: { after: 60 }, children: [new TextRun({ text: "Answer: ______________________________________________", color: "999999" })] }));
    }
  });
  children.push(new Paragraph({ children: [new PageBreak()] }));
});

// ---- Answer key ----
children.push(new Paragraph({ text: "Part 2 — Answer Key", heading: HeadingLevel.HEADING_1 }));
children.push(new Paragraph({ spacing: { after: 120 }, children: [new TextRun({ text: "Short-answer responses show an accepted model answer; award marks for equivalent correct answers.", italics: true, color: "606060" })] }));

QUIZZES.forEach((qz) => {
  children.push(new Paragraph({
    heading: HeadingLevel.HEADING_2, spacing: { before: 160, after: 40 },
    children: [new TextRun({ text: `Quiz ${qz.n} — ${qz.topic}`, bold: true, color: partColor(qz.part) })],
  }));
  qz.q.forEach((item, i) => {
    let ans;
    if (item.t === "m") ans = `${LETTERS[item.a]})  ${item.o[item.a]}`;
    else ans = item.a;
    children.push(new Paragraph({
      spacing: { after: 40 }, indent: { left: 360, hanging: 300 },
      children: [
        new TextRun({ text: `${i + 1}. `, bold: true, color: BLUE }),
        new TextRun({ text: ans }),
      ],
    }));
  });
});

const doc = new Document({
  creator: "Course",
  title: "Advanced Excel & Power BI — Quiz Bank & Answer Keys",
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
  const out = path.join(__dirname, "Quiz_Bank_and_Answer_Keys.docx");
  fs.writeFileSync(out, buf);
  console.log("Wrote", path.basename(out), buf.length, "bytes");
});
