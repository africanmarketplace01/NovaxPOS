const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  PageBreak, TableOfContents, ShadingType,
} = require("docx");

const BLUE = "1F4E79", GREEN = "217346", GOLD = "B8860B", TIP = "2E7D32", CFU = "6A1B9A", CODEBG = "F2F2F2";

/* ---- content model ----
WEEKS: [{ n, part, theme, sessions:[
   { code, title, duration, topics:[
      { name, goal, steps:[...], code:[...formulas...], tip, cfu }
   ]}
]}]
*/
const WEEKS = [
{ n:1, part:"Excel", theme:"Foundations & Data Structuring", sessions:[
  { code:"S1", title:"Excel Power Features + Tables", topics:[
    { name:"Efficient navigation & selection", goal:"Get learners moving without the mouse.",
      steps:[
        "Open sales_data.csv and Save As an .xlsx workbook (File > Save As > Excel Workbook).",
        "Demonstrate Ctrl+Arrow to jump to the edge of data, and Ctrl+Shift+Arrow to select to it.",
        "Show Ctrl+Home (A1) and Ctrl+End (last used cell).",
        "Select a whole column with Ctrl+Space, a whole row with Shift+Space.",
        "Have everyone select the full data block with Ctrl+A (from inside the data)."],
      tip:"Make learners repeat each shortcut twice — muscle memory beats notes.",
      cfu:"Ask: which shortcut jumps to the bottom of a 10,000-row column instantly?" },
    { name:"Absolute vs relative references", goal:"Explain why formulas 'move' when copied.",
      steps:[
        "In an empty cell type =D2*0.075 (a 7.5% tax on Revenue) and fill down — show it adjusts per row.",
        "Put the rate 0.075 in cell H1. Type =D2*H1 and fill down — show the rate reference breaks.",
        "Fix it: =D2*$H$1, fill down again — now the rate stays locked.",
        "Press F4 on a reference while editing to cycle A1 -> $A$1 -> A$1 -> $A1."],
      code:["=D2*$H$1        (lock both)","=D$1 / =$D1     (lock row / lock column only)"],
      tip:"Teach the phrase 'dollar before what you don't want to change'.",
      cfu:"When copying a formula down a column, which part usually needs a $ — the row or the column?" },
    { name:"Named ranges", goal:"Make formulas readable.",
      steps:[
        "Select H1, click the Name Box (left of the formula bar), type TaxRate, press Enter.",
        "Rewrite the tax formula as =D2*TaxRate and fill down.",
        "Open Formulas > Name Manager to show/edit the name."],
      tip:"Named ranges make formulas self-documenting and are reused later in dashboards.",
      cfu:"Why is =Revenue*TaxRate easier to audit than =D2*$H$1?" },
    { name:"Excel Tables & structured references", goal:"The single most important habit in the course.",
      steps:[
        "Click any data cell and press Ctrl+T; confirm 'My table has headers' and click OK.",
        "In Table Design, rename the table to Sales.",
        "Add a header 'LineTotal' in the first empty column and type =[@Quantity]*[@UnitPrice] — watch it fill the whole column automatically.",
        "Add a new row at the bottom and show the formula and formatting auto-extend.",
        "Point out the Total Row checkbox (Table Design > Total Row)."],
      code:["=[@Quantity]*[@UnitPrice]","=SUM(Sales[Revenue])   (reference the whole column)"],
      tip:"Stress: PivotTables and Power BI love Tables because the range grows automatically.",
      cfu:"Name two things that happen automatically when data is inside a Table." },
  ]},
  { code:"S2", title:"Data Cleaning Fundamentals", topics:[
    { name:"Trimming & standardising text", goal:"Fix the invisible problems.",
      steps:[
        "Open raw_orders_messy.csv (note the stray spaces and mixed case).",
        "In a helper column type =TRIM(CLEAN(B2)) to strip spaces and non-printing characters.",
        "Wrap with PROPER: =PROPER(TRIM(B2)) to fix name capitalisation.",
        "Copy the helper column and Paste Special > Values over the original, then delete the helper."],
      code:["=PROPER(TRIM(B2))","=UPPER(A2) / =LOWER(A2)"],
      tip:"Always clean into a helper column first, then paste values back — never overwrite live.",
      cfu:"What is the difference between TRIM and CLEAN?" },
    { name:"Text-to-Columns & Flash Fill", goal:"Split and extract without formulas.",
      steps:[
        "Select the Customer Name column, Data > Text to Columns > Delimited > Space > Finish to split first/last name.",
        "Undo, then demonstrate Flash Fill: in the next column type the first name of row 1, start typing row 2, press Ctrl+E.",
        "Show Flash Fill extracting initials or reformatting a code."],
      tip:"Flash Fill reads your example — give it one clear pattern before pressing Ctrl+E.",
      cfu:"When would you use Text-to-Columns instead of Flash Fill?" },
    { name:"Duplicates & wildcard Find/Replace", goal:"De-dupe and bulk-fix.",
      steps:[
        "Select the data, Data > Remove Duplicates, tick Order ID, click OK and read the 'x duplicates removed' message.",
        "Open Find & Replace (Ctrl+H). Replace '$' with nothing in the Price column.",
        "Show wildcards: Find 'N?rth' to catch the 'N0rth' typo; Find 'North*' with * as any characters.",
        "Set the cleaned Price column to Number format (Ctrl+Shift+1)."],
      tip:"? matches one character, * matches any number of characters.",
      cfu:"Which wildcard would you use to find every entry that STARTS with 'North'?" },
  ]},
  { code:"S3", title:"Logical Functions + Quiz 1", topics:[
    { name:"IF and nested IF", goal:"Decisions in a cell.",
      steps:[
        "On the Sales table add a column 'Size' = IF([@Revenue]>=5000,\"Large\",\"Small\").",
        "Extend to three outcomes with nesting: =IF([@Revenue]>=10000,\"Large\",IF([@Revenue]>=5000,\"Medium\",\"Small\")).",
        "Read the nested formula aloud left-to-right to show how it evaluates."],
      code:['=IF([@Revenue]>=5000,"Large","Small")'],
      tip:"Beyond two levels, nesting gets unreadable — that's the motivation for IFS next.",
      cfu:"In a nested IF, which condition is tested first?" },
    { name:"IFS, AND, OR, NOT", goal:"Cleaner multi-condition logic.",
      steps:[
        "Replace the nested IF with IFS: =IFS([@Revenue]>=10000,\"Large\",[@Revenue]>=5000,\"Medium\",TRUE,\"Small\").",
        "Add 'Priority' = IF(AND([@Quantity]>=5,[@Discount]=0),\"Yes\",\"No\").",
        "Show OR: flag rows that are either high quantity OR discounted."],
      code:['=IFS([@Revenue]>=10000,"Large",[@Revenue]>=5000,"Medium",TRUE,"Small")','=IF(AND([@Quantity]>=5,[@Discount]=0),"Yes","No")'],
      tip:"End IFS with TRUE,\"...\" as the catch-all 'else'.",
      cfu:"What does AND(...) return if only one of two conditions is true?" },
    { name:"IFERROR", goal:"Professional, error-free output.",
      steps:[
        "Create a deliberate error: =[@Revenue]/[@Discount] (division by zero where discount is 0).",
        "Wrap it: =IFERROR([@Revenue]/[@Discount],0) to show 0 instead of #DIV/0!.",
        "Explain this is essential before dashboards and lookups.",
        "Run Quiz 1 in the last 20 minutes."],
      code:["=IFERROR(formula, 0)"],
      tip:"IFERROR keeps dashboards clean, but don't use it to hide real mistakes — test the formula first.",
      cfu:"Name two error values IFERROR can catch." },
  ]},
]},
{ n:2, part:"Excel", theme:"Lookup & Reference Mastery", sessions:[
  { code:"S4", title:"VLOOKUP, HLOOKUP & INDEX+MATCH", topics:[
    { name:"VLOOKUP", goal:"The classic lookup and its trap.",
      steps:[
        "Put products.csv on a sheet named Products; keep Sales on another.",
        "On Sales add 'Cost' = VLOOKUP([@ProductID], Products!$A:$E, 5, FALSE).",
        "Explain each argument: value, table, column number, FALSE=exact.",
        "Insert a column inside Products and show the VLOOKUP now returns the wrong value — the trap."],
      code:["=VLOOKUP([@ProductID], Products!$A:$E, 5, FALSE)"],
      tip:"Always use FALSE for exact match unless the data is sorted and you want banding.",
      cfu:"Why must the lookup key be in the first column of the VLOOKUP table?" },
    { name:"INDEX + MATCH", goal:"The robust, flexible alternative.",
      steps:[
        "Rebuild Cost with INDEX+MATCH: =INDEX(Products!$E:$E, MATCH([@ProductID], Products!$A:$A, 0)).",
        "Explain MATCH returns the row position; INDEX returns the value at that position.",
        "Insert a column in Products again — show INDEX+MATCH still works.",
        "Demonstrate a left-lookup (return a column to the LEFT of the key), which VLOOKUP cannot do."],
      code:["=INDEX(Products!$E:$E, MATCH([@ProductID], Products!$A:$A, 0))"],
      tip:"INDEX+MATCH survives column inserts and can look left — teach it as the professional default.",
      cfu:"Which function returns the position, and which returns the value?" },
  ]},
  { code:"S5", title:"XLOOKUP, Dynamic Arrays & Dependent Lists", topics:[
    { name:"XLOOKUP", goal:"Modern, one-function lookup (Microsoft 365).",
      steps:[
        "Replace the lookup: =XLOOKUP([@ProductID], Products[ProductID], Products[Cost], \"Not found\").",
        "Point out exact match is the default and if_not_found is built in — no IFERROR needed.",
        "Return several columns at once: =XLOOKUP([@ProductID], Products[ProductID], Products[[Product]:[UnitPrice]])."],
      code:['=XLOOKUP([@ProductID], Products[ProductID], Products[Cost], "Not found")'],
      tip:"If learners are on Excel 2019, keep INDEX+MATCH — XLOOKUP needs Microsoft 365/2021.",
      cfu:"What does XLOOKUP do by default that VLOOKUP does not (regarding match type and errors)?" },
    { name:"Dynamic arrays: FILTER, UNIQUE, SORT", goal:"Formulas that spill.",
      steps:[
        "In a blank cell: =UNIQUE(Sales[Region]) — a spilled list appears.",
        "Wrap it: =SORT(UNIQUE(Sales[Region])).",
        "Show FILTER: =FILTER(Sales, Sales[Region]=\"North\") returns all northern orders.",
        "Reference a spill with #: point another formula at the first cell followed by #."],
      code:["=SORT(UNIQUE(Sales[Region]))","=FILTER(Sales, Sales[Region]=\"North\")"],
      tip:"Spill ranges update automatically as data changes — no dragging required.",
      cfu:"What does the # operator (e.g. G2#) refer to?" },
    { name:"Dependent drop-down lists", goal:"Controlled inputs.",
      steps:[
        "Create a named range for each region's salespeople (or use a helper table).",
        "Cell A1: Data > Data Validation > List = your region list.",
        "Cell B1: Data Validation > List = =INDIRECT(A1) so the choices depend on A1.",
        "Test: change A1 and watch B1's options change."],
      code:["=INDIRECT(A1)"],
      tip:"Named ranges must match the exact text chosen in the first drop-down for INDIRECT to work.",
      cfu:"Which function makes the second list depend on the first?" },
  ]},
  { code:"S6", title:"Lookup Lab + Quiz 2", topics:[
    { name:"Build a price-lookup tool", goal:"Combine the week's skills.",
      steps:[
        "Add an input cell with Data Validation listing all ProductIDs.",
        "Return Product, Category and UnitPrice with XLOOKUP (or INDEX+MATCH).",
        "Wrap outputs in IFERROR for a friendly message.",
        "Format the result block as a tidy card with labels."],
      tip:"Circulate and check everyone's Data Validation list points at the right range.",
      cfu:"How would you make the tool show 'Enter an ID' when the input is blank?" },
    { name:"Build an employee finder + Quiz 2", goal:"Independent practice.",
      steps:[
        "Use hr_data.csv. Input = EmployeeID via Data Validation.",
        "Return Name, Department, JobTitle, Salary with XLOOKUP.",
        "Add a FILTER that lists everyone in a chosen Department.",
        "Run Quiz 2 in the final 15 minutes."],
      tip:"The employee finder is the homework deliverable — set expectations before they leave.",
      cfu:"Which function returns a whole list of matching employees rather than a single value?" },
  ]},
]},
{ n:3, part:"Excel", theme:"Analysis, PivotTables & Dashboards", sessions:[
  { code:"S7", title:"PivotTables", topics:[
    { name:"Create a PivotTable", goal:"Summarise thousands of rows in seconds.",
      steps:[
        "Click inside the Sales table, Insert > PivotTable > New Worksheet.",
        "Drag Region to Rows, Category to Columns, Revenue to Values.",
        "Point out the four zones: Rows, Columns, Values, Filters.",
        "Double-click a number to drill into the underlying rows."],
      tip:"Because the source is a Table, new data appears after Refresh with no range editing.",
      cfu:"Which zone determines the numbers being aggregated?" },
    { name:"Value settings & grouping", goal:"Change what the numbers mean.",
      steps:[
        "Right-click a value > Show Values As > % of Grand Total.",
        "Add another Revenue to Values and set it to Running Total.",
        "Right-click an OrderDate in Rows > Group > Months and Years.",
        "Sort a column Largest to Smallest."],
      tip:"'Show Values As' turns a raw total into a share or a trend without any formula.",
      cfu:"How do you turn a revenue total into its percentage of the grand total?" },
    { name:"Slicers & Timelines", goal:"Point-and-click filtering.",
      steps:[
        "PivotTable Analyze > Insert Slicer > Region.",
        "Insert Slicer > Category and arrange both neatly.",
        "PivotTable Analyze > Insert Timeline > OrderDate; filter to a quarter.",
        "Click slicer buttons to show instant filtering."],
      tip:"Slicers are the foundation of the interactive dashboard in S9.",
      cfu:"What is the difference between a Slicer and a Timeline?" },
  ]},
  { code:"S8", title:"Charts & Conditional Formatting", topics:[
    { name:"Combo chart", goal:"Two measures, two scales.",
      steps:[
        "Build a small pivot: Region in Rows, Revenue and Margin% in Values.",
        "Insert > Combo Chart. Set Revenue = Clustered Column, Margin% = Line on Secondary Axis.",
        "Add a chart title and data labels; remove chartjunk (extra gridlines)."],
      tip:"Use a secondary axis only when the two measures have very different scales.",
      cfu:"Why put Margin% on a secondary axis instead of the same axis as Revenue?" },
    { name:"Conditional formatting & sparklines", goal:"See patterns at a glance.",
      steps:[
        "Select a Revenue range, Home > Conditional Formatting > Data Bars.",
        "New Rule > Use a formula: =$D2<500 to highlight low rows; set a red fill.",
        "Add icon sets for Margin% thresholds.",
        "Insert > Sparklines > Line for a per-salesperson monthly trend."],
      code:["=$D2<500   (formula rule to flag low revenue)"],
      tip:"Formula rules unlock formatting that the preset rules can't do — teach the $ carefully.",
      cfu:"Why does the formula rule use $D2 (column locked, row relative)?" },
  ]},
  { code:"S9", title:"Interactive Dashboard Build", topics:[
    { name:"Plan the layout", goal:"Design before building.",
      steps:[
        "Sketch a one-page grid: title bar, 3 KPI cells, two charts, one slicer column.",
        "On a new sheet, hide gridlines (View > Gridlines off) for a clean canvas.",
        "Reserve the top row for KPIs and the left/right edge for slicers."],
      tip:"A quick sketch prevents the 'everything everywhere' dashboard.",
      cfu:"Where should the most important number (headline KPI) sit on the page?" },
    { name:"KPI cells & connected slicers", goal:"One control, many visuals.",
      steps:[
        "Create KPI cells: Total Revenue =SUM(Sales[Revenue]); Top Region via a small pivot or =INDEX/MATCH on a sorted pivot.",
        "Add two PivotCharts (by Region, by Month).",
        "Insert one Region Slicer; right-click > Report Connections and tick both PivotTables.",
        "Apply a consistent colour theme and number formats; freeze the title row."],
      code:["=SUM(Sales[Revenue])"],
      tip:"Report Connections is the magic that makes one slicer drive the whole dashboard.",
      cfu:"How do you make a single slicer filter two different PivotCharts at once?" },
  ]},
]},
{ n:4, part:"Excel", theme:"Functions, Power Query & Excel Capstone", sessions:[
  { code:"S10", title:"Text, Date & Statistical Functions", topics:[
    { name:"Text & date functions", goal:"Reshape and calculate with text and dates.",
      steps:[
        "Build a label: =TEXTJOIN(\" - \",TRUE,[@Region],[@Product]).",
        "On hr_data.csv compute tenure: =DATEDIF([@HireDate],TODAY(),\"Y\") & \" yrs\".",
        "Show EOMONTH([@HireDate],0) for month-end and NETWORKDAYS(start,end) for working days."],
      code:['=TEXTJOIN(" - ",TRUE,[@Region],[@Product])','=DATEDIF([@HireDate],TODAY(),"Y")'],
      tip:"DATEDIF is hidden (no IntelliSense) but reliable for age/tenure in whole years.",
      cfu:"Which function counts working days between two dates, skipping weekends?" },
    { name:"Conditional aggregation", goal:"One-number answers without a pivot.",
      steps:[
        "Total northern electronics: =SUMIFS(Sales[Revenue],Sales[Region],\"North\",Sales[Category],\"Electronics\").",
        "Count discounted orders per region: =COUNTIFS(Sales[Region],\"North\",Sales[Discount],\">0\").",
        "Average order value by region with AVERAGEIFS.",
        "Rank salespeople: =RANK.EQ(value, range)."],
      code:['=SUMIFS(Sales[Revenue],Sales[Region],"North",Sales[Category],"Electronics")'],
      tip:"SUMIFS often replaces a whole PivotTable when you need just one figure on a dashboard.",
      cfu:"What is the argument order for SUMIFS (sum range first or criteria first)?" },
  ]},
  { code:"S11", title:"Power Query, What-If & Macros", topics:[
    { name:"Clean & combine with Power Query", goal:"Repeatable, refreshable cleaning.",
      steps:[
        "Data > Get Data > From Text/CSV > raw_orders_messy.csv > Transform Data.",
        "In the editor: promote headers, set data types, Trim (Transform > Format > Trim), remove errors.",
        "Home > Merge Queries: join with Products on ProductID to bring in Cost.",
        "Home > Close & Load To > Table. Note every step is recorded in Applied Steps."],
      tip:"Emphasise: change the source file, hit Refresh, and all steps re-run automatically.",
      cfu:"What is the difference between Merge and Append in Power Query?" },
    { name:"What-If tools & macros", goal:"Scenario analysis and simple automation.",
      steps:[
        "Build a tiny profit model (Revenue - Cost = Profit).",
        "Data > What-If Analysis > Goal Seek: set Profit to a target by changing Cost.",
        "Scenario Manager: save 'Best' and 'Worst' input sets and switch between them.",
        "View > Macros > Record Macro: record formatting a report, stop, then run it."],
      tip:"Keep the macro trivial — the goal is to demystify automation, not teach VBA.",
      cfu:"What does Goal Seek find — an input or an output?" },
  ]},
  { code:"S12", title:"Excel Capstone + Assessment", topics:[
    { name:"Run the capstone", goal:"Independent end-to-end build.",
      steps:[
        "Restate the brief: clean the messy orders, enrich with product Margin via XLOOKUP, build a dashboard.",
        "Required elements: PivotTable, one Slicer, a combo chart, and 3 KPI cells.",
        "Walk the marking rubric so expectations are explicit.",
        "Give ~2 hours to build; reserve the last 45 minutes for the written assessment."],
      tip:"Circulate early to unblock data-cleaning issues before they cascade.",
      cfu:"Rubric check: which four areas are graded?" },
  ]},
]},
{ n:5, part:"Power BI", theme:"Foundations & Data Preparation", sessions:[
  { code:"S13", title:"Power BI Overview + Getting Data", topics:[
    { name:"Install & tour", goal:"Orient learners in Power BI Desktop.",
      steps:[
        "Confirm everyone has Power BI Desktop installed (free, Windows-only).",
        "Tour the three view icons on the left: Report, Data (table), Model.",
        "Point out the Fields, Visualizations and Filters panes.",
        "Explain the workflow: Get Data -> Transform -> Model -> Visualise -> Publish."],
      tip:"Set the mental model early: Power BI = Power Query + Power Pivot + visuals in one tool.",
      cfu:"Which of the three views is where you create relationships?" },
    { name:"Get data from files", goal:"Load the course datasets.",
      steps:[
        "Home > Get Data > Text/CSV; load sales_data.csv, products.csv, calendar.csv one by one.",
        "For each, choose Transform Data (not Load) to inspect it first.",
        "Show Get Data > Folder to combine many files at once (concept).",
        "Save As SalesModel.pbix."],
      tip:"Loading a Table/CSV that is already clean saves hours later — reinforce Week 1-4 habits.",
      cfu:"When would you use Get Data > Folder instead of a single CSV?" },
  ]},
  { code:"S14", title:"Power Query in Power BI", topics:[
    { name:"Transform & type data", goal:"Same engine as Excel, inside Power BI.",
      steps:[
        "Open Transform Data to enter the Power Query Editor.",
        "Use Transform > Data Type to set OrderDate = Date, Revenue = Decimal, Quantity = Whole Number.",
        "Home > Remove Rows > Remove Blank Rows; Remove Columns you don't need.",
        "Reorder an Applied Step and watch the preview change; then Close & Apply."],
      tip:"Reassure Excel learners: this is Get & Transform with a different coat of paint.",
      cfu:"Why set correct data types before loading into the model?" },
    { name:"Reference vs Duplicate", goal:"Reuse queries correctly.",
      steps:[
        "Right-click the Sales query > Reference to build a derived query from its output.",
        "Contrast with Duplicate (copies all the steps).",
        "Filter the referenced query to a single year as a demo."],
      tip:"Reference = live off the output; Duplicate = independent copy of the steps.",
      cfu:"If you fix a step in the original query, which of Reference/Duplicate inherits the fix?" },
  ]},
  { code:"S15", title:"Shaping Data + Lab", topics:[
    { name:"Merge, append & unpivot", goal:"Combine and reshape tables.",
      steps:[
        "Home > Merge Queries: join Sales with Products on ProductID (Left Outer) and expand Cost.",
        "Home > Append Queries: stack two similarly-structured tables (demo with a duplicate filtered by year).",
        "Select month columns of a wide sample table > Transform > Unpivot Columns.",
        "Split a combined code column: Transform > Split Column > By Delimiter."],
      tip:"The lab output becomes the Week 6 model — have everyone Close & Apply and save.",
      cfu:"Which operation turns wide monthly columns into a tall two-column shape?" },
  ]},
]},
{ n:6, part:"Power BI", theme:"Data Modeling & DAX Basics", sessions:[
  { code:"S16", title:"Data Modeling & the Star Schema", topics:[
    { name:"Build relationships", goal:"Connect fact and dimension tables.",
      steps:[
        "Switch to Model view.",
        "Drag Sales[ProductID] onto Products[ProductID] to create a relationship.",
        "Drag Sales[OrderDate] onto Calendar[Date].",
        "Double-click a relationship line to confirm cardinality Many-to-One and single cross-filter direction."],
      tip:"Draw the star on the board: Sales in the centre, Products and Calendar as points.",
      cfu:"In a sales model, which table is the 'many' side of the relationship?" },
    { name:"Cardinality & filter direction", goal:"Understand how filters flow.",
      steps:[
        "Explain 1-to-many: one product, many sales rows.",
        "Show single vs both cross-filter direction and warn against 'both' unless needed.",
        "Point out active (solid) vs inactive (dashed) relationship lines.",
        "Hide key columns you won't use in visuals (right-click > Hide)."],
      tip:"A clean star schema makes DAX simple; a flat or tangled model makes it painful.",
      cfu:"Why avoid many-to-many and bidirectional filters when a star schema will do?" },
  ]},
  { code:"S17", title:"DAX Basics", topics:[
    { name:"Measures vs calculated columns", goal:"Choose the right tool.",
      steps:[
        "Create a measure: right-click Sales > New Measure > Total Revenue = SUM(Sales[Revenue]).",
        "Create a calculated column for comparison and explain it is stored per row.",
        "State the rule: prefer measures for anything you aggregate.",
        "Format the measure as currency in the Measure tools ribbon."],
      code:["Total Revenue = SUM(Sales[Revenue])","Total Cost = SUMX(Sales, Sales[Quantity]*RELATED(Products[Cost]))"],
      tip:"Measures recalculate with the filter context of each visual — columns don't.",
      cfu:"Which should you use for a KPI that must respond to slicers — a measure or a column?" },
    { name:"CALCULATE, IF & SWITCH", goal:"Change context and branch logic.",
      steps:[
        "Electronics Revenue = CALCULATE([Total Revenue], Products[Category]=\"Electronics\").",
        "Explain CALCULATE modifies the filter context — the most important DAX idea.",
        "Add Margin = [Total Revenue] - [Total Cost] and Margin % = DIVIDE([Margin],[Total Revenue]).",
        "Show a SWITCH(TRUE(), ...) label measure for banding."],
      code:['Electronics Revenue = CALCULATE([Total Revenue], Products[Category]="Electronics")','Margin % = DIVIDE([Margin],[Total Revenue])'],
      tip:"Go slow on filter vs row context — it's the hardest idea in the whole course.",
      cfu:"What does CALCULATE change about how a measure is evaluated?" },
  ]},
  { code:"S18", title:"DAX Practice Lab + Quiz", topics:[
    { name:"Author the core measures", goal:"Build a reusable measure set.",
      steps:[
        "Create Total Revenue, Total Cost, Margin, Margin %, and Avg Order Value = DIVIDE([Total Revenue], DISTINCTCOUNT(Sales[OrderID])).",
        "Drop all five into a Matrix visual by Category to verify results.",
        "Add Orders = DISTINCTCOUNT(Sales[OrderID]) and Units = SUM(Sales[Quantity]).",
        "Run the DAX quiz at the end."],
      code:["Avg Order Value = DIVIDE([Total Revenue], DISTINCTCOUNT(Sales[OrderID]))"],
      tip:"Testing measures in a matrix before building visuals catches errors early.",
      cfu:"Why use DIVIDE instead of / for Margin %?" },
  ]},
]},
{ n:7, part:"Power BI", theme:"Advanced DAX & Visualization", sessions:[
  { code:"S19", title:"Time Intelligence", topics:[
    { name:"Date table & YTD", goal:"Enable time calculations.",
      steps:[
        "Select the Calendar table > Table tools > Mark as Date Table > choose Date.",
        "Revenue YTD = TOTALYTD([Total Revenue], Calendar[Date]).",
        "Put Revenue YTD in a matrix by month to see it accumulate.",
        "Explain why a continuous, gap-free date table is required."],
      code:["Revenue YTD = TOTALYTD([Total Revenue], Calendar[Date])"],
      tip:"Reuse calendar.csv — it is already continuous across 2024-2025.",
      cfu:"Why can't time intelligence run reliably off the dates in the Sales table alone?" },
    { name:"Prior year & YoY growth", goal:"Compare across periods.",
      steps:[
        "Revenue LY = CALCULATE([Total Revenue], SAMEPERIODLASTYEAR(Calendar[Date])).",
        "YoY % = DIVIDE([Total Revenue]-[Revenue LY], [Revenue LY]).",
        "Add a MoM measure with DATEADD(Calendar[Date], -1, MONTH).",
        "Visualise YoY % on a line by month."],
      code:["Revenue LY = CALCULATE([Total Revenue], SAMEPERIODLASTYEAR(Calendar[Date]))","YoY % = DIVIDE([Total Revenue]-[Revenue LY],[Revenue LY])"],
      tip:"Format YoY % as a percentage and consider conditional colour for negatives.",
      cfu:"Which function shifts the date context back exactly one year?" },
  ]},
  { code:"S20", title:"Advanced DAX", topics:[
    { name:"ALL, RANKX & VAR", goal:"Ratios, rankings and readable code.",
      steps:[
        "% of Total = DIVIDE([Total Revenue], CALCULATE([Total Revenue], ALL(Products))).",
        "Salesperson Rank = RANKX(ALL(Sales[Salesperson]), [Total Revenue]).",
        "Refactor a measure with VAR: compute an intermediate value once, then RETURN.",
        "Compare readability before/after."],
      code:["% of Total = DIVIDE([Total Revenue], CALCULATE([Total Revenue], ALL(Products)))","Rank = RANKX(ALL(Sales[Salesperson]), [Total Revenue])"],
      tip:"ALL removes filters so the denominator stays the grand total while the numerator is filtered.",
      cfu:"What does ALL(Products) do to the filter context inside CALCULATE?" },
  ]},
  { code:"S21", title:"Visuals & Interactivity", topics:[
    { name:"Core visuals", goal:"Right visual for the message.",
      steps:[
        "Add Card visuals for Total Revenue and Margin %.",
        "Add a Line chart of Revenue by month and a Matrix by Category.",
        "Apply conditional formatting to a matrix value (background colour by value).",
        "Tidy titles, data labels and number formats."],
      tip:"One message per visual — link back to the dataviz colour/clarity rules.",
      cfu:"Which visual is best for a single headline number?" },
    { name:"Drill-through, bookmarks & slicers", goal:"Make the report interactive.",
      steps:[
        "Create a Product detail page; drag Product to the Drill-through well.",
        "Right-click a product on the summary > Drill through > detail page.",
        "Add a Region slicer; Format > Sync slicers to apply it across pages.",
        "View > Bookmarks: capture a filtered state and wire a button to it."],
      tip:"Drill-through + a Back button feels professional and is quick to set up.",
      cfu:"What does a drill-through page receive from the visual you right-clicked?" },
  ]},
]},
{ n:8, part:"Power BI", theme:"Report Design, Publishing & Final Capstone", sessions:[
  { code:"S22", title:"Report Design + Power BI Service", topics:[
    { name:"Theme & layout", goal:"A polished, consistent report.",
      steps:[
        "View > Themes > choose or Browse for a custom theme JSON.",
        "Align visuals with the Format > alignment tools and a consistent grid.",
        "Add navigation buttons (Insert > Buttons) and set a Mobile layout (View > Mobile layout).",
        "Keep to one clear message per page."],
      tip:"A saved theme JSON enforces brand colours across every visual automatically.",
      cfu:"Where do you build the phone version of a report?" },
    { name:"Publish to the Service", goal:"Share your work online.",
      steps:[
        "Home > Publish > choose a workspace; sign in with a Microsoft account.",
        "Open the report in the Power BI Service (app.powerbi.com).",
        "Pin two visuals to a new Dashboard and explain report vs dashboard.",
        "Show the workspace where teammates would find it."],
      tip:"Dashboards are single-page tile collages pinned from one or more reports.",
      cfu:"What is the difference between a report and a dashboard in the Service?" },
  ]},
  { code:"S23", title:"Sharing, Refresh & RLS", topics:[
    { name:"Row-Level Security", goal:"Restrict data by role.",
      steps:[
        "In Desktop: Modeling > Manage Roles > New; name it 'North'.",
        "Add a rule on Sales: [Region] = \"North\".",
        "Test with Modeling > View As > North.",
        "Publish and assign members to the role in the Service (concept)."],
      code:['Table filter DAX:  [Region] = "North"'],
      tip:"RLS filters rows by the signed-in user's role — vital for multi-branch data.",
      cfu:"Where do you test an RLS role before publishing?" },
    { name:"Scheduled refresh & capstone build", goal:"Keep data current; build time.",
      steps:[
        "In the Service, open dataset Settings > Scheduled refresh (walk through the options).",
        "Explain the on-premises data gateway for local files (concept only).",
        "Give the remainder of the session to supervised capstone building.",
        "Circulate and unblock model/measure issues."],
      tip:"Local file sources need a gateway for automatic refresh — cloud sources usually don't.",
      cfu:"Why might a report built on a desktop CSV fail to refresh in the Service?" },
  ]},
  { code:"S24", title:"Final Capstone Presentations + Exam", topics:[
    { name:"Present & assess", goal:"Demonstrate mastery.",
      steps:[
        "Each learner gives a 5-minute walkthrough of their published report.",
        "They explain the star schema and two key measures.",
        "Peers and instructor ask questions against the rubric.",
        "Administer the final written assessment; then share further-learning resources."],
      tip:"Have learners present in the Service, not Desktop, to prove they published successfully.",
      cfu:"Rubric check: what share of the grade is the final capstone worth?" },
  ]},
]},
];

// ---------------- rendering ----------------
const partColor = (p) => (p === "Excel" ? GREEN : GOLD);
const codeParas = (arr, color) => arr.map((line) => new Paragraph({
  spacing: { after: 20 }, shading: { type: ShadingType.CLEAR, color: "auto", fill: CODEBG },
  children: [new TextRun({ text: line, font: "Consolas", size: 18, color })],
}));

const children = [];
children.push(
  new Paragraph({ text: "", spacing: { before: 1900 } }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "Advanced Excel & Power BI", bold: true, size: 50, color: BLUE })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 120 }, children: [new TextRun({ text: "Instructor Practical Teaching Guide", size: 38, color: "404040" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 200 }, children: [new TextRun({ text: "Step-by-step demo scripts for every topic · 8 weeks · 24 sessions", size: 20, italics: true, color: "606060" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 240 }, children: [new TextRun({ text: "For each topic: Demo steps  ·  Formula / DAX  ·  Teaching tip  ·  Check for understanding", size: 16, color: "808080" })] }),
  new Paragraph({ children: [new PageBreak()] }),
);
children.push(new Paragraph({ text: "Contents", heading: HeadingLevel.HEADING_1 }));
children.push(new TableOfContents("Contents", { hyperlink: true, headingStyleRange: "1-2" }));
children.push(new Paragraph({ children: [new PageBreak()] }));

// how to use
children.push(new Paragraph({ text: "How to use this guide", heading: HeadingLevel.HEADING_1 }));
children.push(new Paragraph({ spacing: { after: 100 }, children: [new TextRun("This is the instructor's live-teaching script. Each session is broken into topics; each topic gives the exact steps to demonstrate (menus, clicks and values), the formula or DAX to type, a teaching tip, and a quick question to check understanding before moving on. Pair it with the matching week's slide deck (talking points) and the Student Workbook (learner exercises). All demos use the provided datasets.")] }));
children.push(new Paragraph({ children: [new PageBreak()] }));

WEEKS.forEach((wk) => {
  children.push(new Paragraph({
    heading: HeadingLevel.HEADING_1, spacing: { before: 120, after: 60 },
    children: [
      new TextRun({ text: `Week ${wk.n} — `, bold: true, color: partColor(wk.part), size: 32 }),
      new TextRun({ text: wk.theme, bold: true, color: BLUE, size: 32 }),
    ],
  }));
  children.push(new Paragraph({ spacing: { after: 100 }, children: [new TextRun({ text: `${wk.part} · Sessions ${wk.sessions[0].code}–${wk.sessions[wk.sessions.length-1].code}`, italics: true, color: "808080", size: 18 })] }));

  wk.sessions.forEach((se) => {
    children.push(new Paragraph({
      heading: HeadingLevel.HEADING_2, spacing: { before: 160, after: 40 },
      children: [new TextRun({ text: `${se.code} · ${se.title}`, bold: true, color: BLUE })],
    }));
    se.topics.forEach((tp, ti) => {
      children.push(new Paragraph({
        spacing: { before: 120, after: 30 },
        children: [
          new TextRun({ text: `Topic ${ti + 1}:  `, bold: true, color: partColor(wk.part) }),
          new TextRun({ text: tp.name, bold: true, color: "202020" }),
        ],
      }));
      if (tp.goal) children.push(new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text: tp.goal, italics: true, color: "606060", size: 19 })] }));
      // demo steps
      children.push(new Paragraph({ spacing: { after: 20 }, children: [new TextRun({ text: "Demo steps", bold: true, size: 19, color: "404040" })] }));
      tp.steps.forEach((st, i) => children.push(new Paragraph({
        spacing: { after: 30 }, indent: { left: 380, hanging: 250 },
        children: [new TextRun({ text: `${i + 1}. `, bold: true }), new TextRun({ text: st, size: 20 })],
      })));
      // code
      if (tp.code && tp.code.length) {
        children.push(new Paragraph({ spacing: { before: 40, after: 10 }, children: [new TextRun({ text: "Formula / DAX", bold: true, size: 19, color: "404040" })] }));
        codeParas(tp.code, partColor(wk.part) === GREEN ? "0E4429" : "6b4e00").forEach((p) => children.push(p));
      }
      // tip
      if (tp.tip) children.push(new Paragraph({ spacing: { before: 50, after: 20 }, children: [new TextRun({ text: "Teaching tip:  ", bold: true, color: TIP }), new TextRun({ text: tp.tip, size: 20, color: "303030" })] }));
      // cfu
      if (tp.cfu) children.push(new Paragraph({ spacing: { after: 60 }, children: [new TextRun({ text: "Check for understanding:  ", bold: true, color: CFU }), new TextRun({ text: tp.cfu, size: 20, italics: true, color: "303030" })] }));
    });
  });
  children.push(new Paragraph({ children: [new PageBreak()] }));
});

const doc = new Document({
  creator: "Course",
  title: "Advanced Excel & Power BI — Instructor Practical Teaching Guide",
  features: { updateFields: true },
  styles: {
    default: { document: { run: { font: "Calibri", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true, run: { size: 32, bold: true, color: BLUE }, paragraph: { spacing: { before: 240, after: 120 } } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true, run: { size: 25, bold: true, color: "2E5496" }, paragraph: { spacing: { before: 180, after: 60 } } },
    ],
  },
  sections: [{ properties: { page: { size: { width: 12240, height: 15840 }, margin: { top: 1080, bottom: 1080, left: 1080, right: 1080 } } }, children }],
});
Packer.toBuffer(doc).then((buf) => {
  const out = path.join(__dirname, "Instructor_Practical_Teaching_Guide.docx");
  fs.writeFileSync(out, buf);
  console.log("Wrote", path.basename(out), buf.length, "bytes");
});
