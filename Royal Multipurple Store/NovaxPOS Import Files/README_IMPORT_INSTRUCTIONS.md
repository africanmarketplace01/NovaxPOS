# Royal Multipurple Store — NovaxPOS Import Files

Generated from the 5 source documents in `Royal Multipurple Store\Location`
(Books-in-Store, Other Goods-in-Store, Project-in-Store, Mount Barclay, Stephen Tulbort).

**Every file below is a CSV** — NovaxPOS's importers only accept `.csv`, so save/keep
these as `.csv` (don't re-save as `.xlsx`) when uploading.

**All prices are now in a single currency: USD.** Everything originally recorded in
Liberian Dollars has been converted at **1 USD = 200 LRD** and merged into one
product file — see "Currency" below for details.

## Import order (do this in sequence)

| # | File | Where to upload in NovaxPOS | What it does |
|---|------|------------------------------|---------------|
| 1 | `01_categories_import.csv` | Products → Category → Import | Creates the 9 categories (matches the folder names you already use for product photos) |
| 2 | `02_units_import.csv` | Products → Unit → Import | Creates the "Piece" (PCS) base unit used by every product |
| 3 | `03_warehouses_import.csv` | Products → Warehouse → Import | Creates **Mount Barclay** and **Stephen Tulbort** as warehouses. Your main store location is whatever warehouse already exists by default in your NovaxPOS install — rename it to "Royal Multipurple Store" if it isn't already, or tell me its name and I'll re-tag the opening-stock file for it. |
| 4 | `04_products_import.csv` | Products → Import | All **153 products**, all priced in USD |
| 5 | `05_opening_stock_STORE.csv` | Purchases → Add by CSV | Loads on-hand quantities into your **main store**. On the upload screen pick your main store's warehouse, any supplier (e.g. create a placeholder "Opening Stock" supplier), and status **Received**. |
| 6 | `06_opening_stock_MountBarclay.csv` | Purchases → Add by CSV | Same as above but pick warehouse **Mount Barclay** |
| 7 | `07_opening_stock_StephenTulbort.csv` | Purchases → Add by CSV | Same as above but pick warehouse **Stephen Tulbort** |

Products must be imported (step 4) **before** the opening-stock files (steps 5–7),
since opening stock is matched to products by their code.

**Note:** if you previously downloaded `04a_products_LRD_import.csv` and
`04b_products_USD_import.csv` from an earlier version of this folder, **delete
both of them** — they've been replaced by the single `04_products_import.csv`
file above. Importing the old 4a/4b files as well as the new one would create
duplicate products.

## Why opening stock is a separate step

NovaxPOS's product importer always sets starting quantity to 0 for every location —
that's how the app is built. Real on-hand quantities have to go in through the
Purchases "Add by CSV" screen instead, once per location. Files 5–7 are built for
that screen.

## Decisions made while building these files

- **Currency — everything converted to USD at 1 USD = 200 LRD**: your source
  documents mixed LRD and USD across products (and in 42 cases, even within the
  same product — cost in one currency, selling price in the other). To keep the
  whole catalog on one consistent currency, every LRD figure (cost, price, and
  wholesale price) has been converted to USD at the rate you confirmed
  (**1 USD = 200 LRD**) and merged into the single `04_products_import.csv` file.
  For any product that was originally in LRD, the original LRD figures are kept in
  its notes (visible in NovaxPOS after import) in case you ever need to re-check
  one — e.g. `Originally listed at 300.0 LRD cost / 700.0 LRD price - converted to
  USD at 1 USD = 200 LRD.` If the real rate at the time you priced these was
  different, let me know the correct rate and I'll regenerate the files.
- **Price ranges** (e.g. "$600 - $700 LRD"): the **higher** number was used as the
  selling price, per your choice (converted to USD along with everything else if
  it was originally in LRD).
- **12 products still have no cost recorded at all** (the source document simply
  never listed a buying price, mostly in the "Other Goods" and "Project Books"
  lists). These import fine and are sellable, but you'll want to fill in their
  cost yourself for accurate profit-margin reporting. A fillable template for
  these is in `REVIEW_A_missing_cost_only.csv` — send this to the client to fill
  in and send back.
- **8 warehouse-only items** (in Mount Barclay's and Stephen Tulbort's lists) didn't
  match any title in the main store price list closely enough to reuse — mostly
  Montessori/Nursery titles listed with a slightly different grade tag in each
  document (e.g. "Mont. Literacy Skill Nursery 1" in the store list vs.
  "Mont. Literacy Skill Nursery -2" in the warehouse list). These were added as new
  products with cost/price = 0 rather than silently guessing they were the same
  book. A fillable template for these is in
  `REVIEW_B_warehouse_only_no_price.csv` — send this to the client too. If any of
  them are actually the same title as one already in the catalog, just delete the
  duplicate in NovaxPOS after import and adjust quantities on the surviving one.
- All 62 items above (currency-converted, no-cost, and warehouse-only) are listed
  in `00_REVIEW_flagged_items.csv` for your records — not an import file, just a
  reference so you know exactly which products to double-check.
- **Categories**: mapped to the same names you already use for your product-photo
  folders (Bibles, ECD Books, Elementary Books, Junior High Books, Senior High,
  General Readers, General Test Books, Measurement Tools, Project Books) so the
  catalog matches how you already think about the inventory.
- **Product codes**: none existed in your source documents, so each product was
  given a new code (e.g. `ELM-0005` for an Elementary book, `BIB-0003` for a Bible,
  `SHS-0012` for Senior High, etc.) — prefix by category, sequential number.
- Two entries named **"Antony And Cleopatra"** appear twice in the source document
  with different quantities/prices (looks like two separate print runs/editions) —
  both were kept as separate products (`GRD-0002` and `GRD-0003`); merge them in
  NovaxPOS if they're actually the same edition.

## Files in this folder

- `00_REVIEW_flagged_items.csv` — the 62 flagged products, for your records (not an import file)
- `01_categories_import.csv`
- `02_units_import.csv`
- `03_warehouses_import.csv`
- `04_products_import.csv` — all 153 products, all in USD
- `05_opening_stock_STORE.csv` (138 stock lines)
- `06_opening_stock_MountBarclay.csv` (27 stock lines)
- `07_opening_stock_StephenTulbort.csv` (9 stock lines)
- `REVIEW_A_missing_cost_only.csv` — 12 items with a selling price but no cost; send to the client to fill in
- `REVIEW_B_warehouse_only_no_price.csv` — 8 warehouse-only items with no price data at all; send to the client to fill in

**153 products total**, **174 stock lines** across the 3 locations, **all in USD**.
