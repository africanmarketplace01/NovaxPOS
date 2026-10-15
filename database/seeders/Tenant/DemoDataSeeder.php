<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * ============================================================================
 *  DemoDataSeeder  —  Rich demo dataset for video tutorials / client demos
 * ============================================================================
 *  Seeds realistic sample data across the whole POS operation:
 *    · Catalogue      → categories, brands, units, taxes, products (+images)
 *    · Parties        → suppliers, customers, customer groups, billers
 *    · Inventory      → per-warehouse stock, transfers, adjustments
 *    · Transactions   → purchases, sales (POS + regular), quotations,
 *                       returns, payments  (spread across the last 90 days)
 *    · Accounting     → accounts, expenses, incomes, money transfers,
 *                       coupons, gift cards
 *    · HRM            → departments, designations, shifts, employees,
 *                       attendance, leaves, holidays, payrolls
 *    · Add-ons        → dining tables, couriers, deliveries
 *
 *  SAFE TO RUN on an already-initialised tenant: it builds on top of the
 *  base records created by TenantDatabaseSeeder and SKIPS itself if the demo
 *  data is already present (detected via the "Beverages" category).
 *
 *  Run:   php artisan tenants:run db:seed --option="class=Database\\Seeders\\Tenant\\DemoDataSeeder"
 *   or (inside a tenant context):  php artisan db:seed --class=Database\\Seeders\\Tenant\\DemoDataSeeder
 *
 *  Product / category / brand images are generated into
 *  public/images/{product,category,brand}. The image file names are derived
 *  from database/seeders/data/demo_catalog.json.
 * ============================================================================
 */
class DemoDataSeeder extends Seeder
{
    /** @var array<string,array<string>> cache of column names per table */
    private array $colCache = [];

    /** @var array<string,int> unique reference counters keyed by prefix */
    private array $refCounter = [];

    public function run(): void
    {
        if (Schema::hasTable('categories')
            && DB::table('categories')->where('name', 'Beverages')->exists()) {
            $this->command?->warn('DemoDataSeeder: demo data already present — refreshing existing demo rows.');
        }

        mt_srand(20260708); // deterministic pseudo-randomness

        $catalog = $this->loadCatalog();

        DB::beginTransaction();
        try {
            $ids = [];
            $ids += $this->seedFoundation();                       // units, taxes, warehouses, accounts, groups
            $ids += $this->seedCatalogue($catalog, $ids);          // categories, brands, products, stock
            $ids += $this->seedParties();                          // suppliers, customers, billers
            $this->seedPurchases($ids);                            // + product_purchases, payments, stock++
            $this->seedSales($ids);                                // + product_sales, payments, stock--
            $this->seedQuotations($ids);
            $this->seedTransfers($ids);
            $this->seedAdjustments($ids);
            $this->seedReturns($ids);
            $this->seedAccounting($ids);                           // expenses, incomes, transfers, coupons, gift cards
            $this->seedHrm($ids);                                  // departments … payrolls
            $this->seedAddons($ids);                               // tables, couriers, deliveries
            $this->finaliseStock($ids);                            // write final qty to product_warehouse + products
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->command?->info('DemoDataSeeder: rich demo dataset created successfully.');
    }

    /* ===================================================================== */
    /*  Helpers                                                              */
    /* ===================================================================== */

    private function cols(string $table): array
    {
        return $this->colCache[$table] ??= Schema::getColumnListing($table);
    }

    /** Insert or update a row, keeping only columns that exist; returns the row id. */
    private function ins(string $table, array $row, ?array $match = null): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $cols = $this->cols($table);
        $filtered = array_intersect_key($row, array_flip($cols));

        if ($match === null) {
            $match = $this->defaultMatch($table, $filtered);
        }

        if (!empty($match)) {
            $query = DB::table($table);
            foreach ($match as $field => $value) {
                $query->where($field, $value);
            }

            $existing = $query->first();
            if ($existing) {
                $payload = $filtered;
                unset($payload['created_at']);
                if (array_key_exists('updated_at', $payload) || array_key_exists('updated_at', $filtered)) {
                    $payload['updated_at'] = $payload['updated_at'] ?? now();
                }
                DB::table($table)->where('id', $existing->id)->update($payload);
                return (int) $existing->id;
            }
        }

        return (int) DB::table($table)->insertGetId($filtered);
    }

    private function defaultMatch(string $table, array $row): array
    {
        $candidates = [
            ['reference_no', 'reference_no'],
            ['code', 'code'],
            ['unit_code', 'unit_code'],
            ['account_no', 'account_no'],
            ['card_no', 'card_no'],
            ['email', 'email'],
            ['title', 'title'],
            ['name', 'name'],
            ['slug', 'slug'],
            ['table_no', 'table_no'],
            ['staff_id', 'staff_id'],
        ];

        foreach ($candidates as [$inputKey, $matchKey]) {
            if (array_key_exists($inputKey, $row) && $row[$inputKey] !== null && $row[$inputKey] !== '') {
                return [$matchKey => $row[$inputKey]];
            }
        }

        if (array_key_exists('sale_id', $row) && $row['sale_id'] !== null) {
            return ['sale_id' => $row['sale_id']];
        }

        if (array_key_exists('purchase_id', $row) && $row['purchase_id'] !== null) {
            return ['purchase_id' => $row['purchase_id']];
        }

        if (array_key_exists('transfer_id', $row) && $row['transfer_id'] !== null) {
            return ['transfer_id' => $row['transfer_id']];
        }

        if (array_key_exists('adjustment_id', $row) && $row['adjustment_id'] !== null) {
            return ['adjustment_id' => $row['adjustment_id']];
        }

        if (array_key_exists('return_id', $row) && $row['return_id'] !== null) {
            return ['return_id' => $row['return_id']];
        }

        if (array_key_exists('quotation_id', $row) && $row['quotation_id'] !== null) {
            return ['quotation_id' => $row['quotation_id']];
        }

        if (array_key_exists('sale_id', $row) && array_key_exists('product_id', $row)) {
            return ['sale_id' => $row['sale_id'], 'product_id' => $row['product_id']];
        }

        if (array_key_exists('purchase_id', $row) && array_key_exists('product_id', $row)) {
            return ['purchase_id' => $row['purchase_id'], 'product_id' => $row['product_id']];
        }

        if (array_key_exists('transfer_id', $row) && array_key_exists('product_id', $row)) {
            return ['transfer_id' => $row['transfer_id'], 'product_id' => $row['product_id']];
        }

        if (array_key_exists('adjustment_id', $row) && array_key_exists('product_id', $row)) {
            return ['adjustment_id' => $row['adjustment_id'], 'product_id' => $row['product_id']];
        }

        if (array_key_exists('return_id', $row) && array_key_exists('product_id', $row)) {
            return ['return_id' => $row['return_id'], 'product_id' => $row['product_id']];
        }

        if (array_key_exists('quotation_id', $row) && array_key_exists('product_id', $row)) {
            return ['quotation_id' => $row['quotation_id'], 'product_id' => $row['product_id']];
        }

        return [];
    }

    private function ref(string $prefix): string
    {
        $this->refCounter[$prefix] = ($this->refCounter[$prefix] ?? 0) + 1;
        return $prefix . date('ymd') . '-' . str_pad((string) $this->refCounter[$prefix], 4, '0', STR_PAD_LEFT);
    }

    private function daysAgo(int $max): Carbon
    {
        return Carbon::now()
            ->subDays(mt_rand(0, $max))
            ->setTime(mt_rand(8, 20), mt_rand(0, 59), mt_rand(0, 59));
    }

    private function pick(array $arr)
    {
        return $arr[array_rand($arr)];
    }

    private function loadCatalog(): array
    {
        $path = database_path('seeders/data/demo_catalog.json');
        if (!is_file($path)) {
            throw new \RuntimeException("Demo catalog not found at {$path}. Run the image generator first.");
        }
        return json_decode(file_get_contents($path), true);
    }

    private function firstId(string $table, array $where = []): ?int
    {
        $q = DB::table($table);
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }
        $row = $q->first();
        return $row->id ?? null;
    }

    /* ===================================================================== */
    /*  Foundation (units, taxes, warehouses, accounts, customer groups)     */
    /* ===================================================================== */

    private function seedFoundation(): array
    {
        $ids = [];
        // Base records created by TenantDatabaseSeeder
        $ids['warehouse_main'] = $this->firstId('warehouses') ?? 1;
        $ids['unit_pc']        = $this->firstId('units', ['unit_code' => 'Pc']) ?? 1;
        $ids['tax_vat']        = $this->firstId('taxes') ?? 1;
        $ids['biller']         = $this->firstId('billers') ?? 1;
        $ids['account_main']   = $this->firstId('accounts') ?? 1;
        $ids['admin']          = $this->firstId('users') ?? 1;
        $ids['group_general']  = $this->firstId('customer_groups') ?? 1;

        // Extra warehouse (for transfers)
        $ids['warehouse_branch'] = $this->ins('warehouses', [
            'name' => 'Downtown Branch', 'phone' => '+1 202 555 0148',
            'email' => 'downtown@novaxpos.demo', 'address' => '221 Market Street, Springfield',
            'is_active' => 1,
        ]);

        // Additional units
        $units = [
            ['unit_code' => 'Box', 'unit_name' => 'Box',   'operator' => '*', 'operation_value' => 12],
            ['unit_code' => 'Kg',  'unit_name' => 'Kilogram', 'operator' => '*', 'operation_value' => 1],
            ['unit_code' => 'Ltr', 'unit_name' => 'Litre', 'operator' => '*', 'operation_value' => 1],
            ['unit_code' => 'Dzn', 'unit_name' => 'Dozen', 'operator' => '*', 'operation_value' => 12],
        ];
        $ids['units'] = [$ids['unit_pc']];
        foreach ($units as $u) {
            $ids['units'][$u['unit_code']] = $this->ins('units', array_merge($u, [
                'base_unit' => null, 'is_active' => 1,
            ]));
        }
        $ids['unit_by_code'] = [
            'Pc' => $ids['unit_pc'],
            'Box' => $ids['units']['Box'], 'Kg' => $ids['units']['Kg'],
            'Ltr' => $ids['units']['Ltr'], 'Dzn' => $ids['units']['Dzn'],
        ];

        // Additional taxes
        $ids['tax_gst']  = $this->ins('taxes', ['name' => 'GST 5%', 'rate' => 5, 'is_active' => 1]);
        $ids['tax_none'] = $this->ins('taxes', ['name' => 'No Tax 0%', 'rate' => 0, 'is_active' => 1]);

        // Additional accounts
        $ids['account_cash'] = $this->ins('accounts', [
            'account_no' => '100200300', 'name' => 'Cash Drawer', 'initial_balance' => 500,
            'total_balance' => 500, 'note' => 'Front-desk cash register account',
            'is_default' => 0, 'is_active' => 1, 'type' => 'Cash', 'is_payment' => 1,
        ]);
        $ids['account_bank'] = $this->ins('accounts', [
            'account_no' => '400500600', 'name' => 'Bank — Main Operating', 'initial_balance' => 25000,
            'total_balance' => 25000, 'note' => 'Primary business bank account',
            'is_default' => 0, 'is_active' => 1, 'type' => 'Bank Account', 'is_payment' => 1,
        ]);
        $ids['accounts'] = [$ids['account_main'], $ids['account_cash'], $ids['account_bank']];

        // Additional customer groups
        $ids['group_wholesale'] = $this->ins('customer_groups', ['name' => 'Wholesale', 'percentage' => '10', 'is_active' => 1]);
        $ids['group_vip']       = $this->ins('customer_groups', ['name' => 'VIP', 'percentage' => '5', 'is_active' => 1]);
        $ids['group_staff']     = $this->ins('customer_groups', ['name' => 'Staff', 'percentage' => '15', 'is_active' => 1]);
        $ids['groups'] = [$ids['group_general'], $ids['group_wholesale'], $ids['group_vip'], $ids['group_staff']];

        return $ids;
    }

    /* ===================================================================== */
    /*  Catalogue (categories, brands, products, opening stock)              */
    /* ===================================================================== */

    private function seedCatalogue(array $catalog, array $ids): array
    {
        $out = ['category' => [], 'brand' => [], 'product' => [], 'stock' => []];

        foreach ($catalog['categories'] as $c) {
            $out['category'][$c['name']] = $this->ins('categories', [
                'name' => $c['name'], 'parent_id' => 0, 'image' => $c['image'], 'is_active' => 1,
            ]);
        }
        foreach ($catalog['brands'] as $b) {
            $out['brand'][$b['name']] = $this->ins('brands', [
                'title' => $b['name'], 'image' => $b['image'], 'is_active' => 1,
            ]);
        }

        $whMain = $ids['warehouse_main'];
        $whBranch = $ids['warehouse_branch'];

        foreach ($catalog['products'] as $p) {
            $unitId = $ids['unit_by_code'][$p['unit']] ?? $ids['unit_pc'];
            $taxId  = $this->pick([$ids['tax_vat'], $ids['tax_gst'], $ids['tax_none']]);
            $pid = $this->ins('products', [
                'name'              => $p['name'],
                'code'              => $p['code'],
                'type'              => 'standard',
                'slug'              => Str::slug($p['name']),
                'barcode_symbology' => 'C128',
                'brand_id'          => $out['brand'][$p['brand']] ?? null,
                'category_id'       => $out['category'][$p['category']],
                'unit_id'           => $unitId,
                'purchase_unit_id'  => $unitId,
                'sale_unit_id'      => $unitId,
                'cost'              => $p['cost'],
                'price'             => $p['price'],
                'wholesale_price'   => round($p['price'] * 0.9, 2),
                'qty'               => 0,
                'alert_quantity'    => 15,
                'tax_id'            => $taxId,
                'tax_method'        => 1,           // 1 = exclusive
                'image'             => $p['image'],
                'featured'          => mt_rand(0, 4) === 0 ? 1 : 0,
                'product_details'   => $p['name'] . ' — sample demo product for tutorials.',
                'is_active'         => 1,
                'is_variant'        => 0,
                'is_batch'          => 0,
                'is_imei'           => 0,
                'is_embeded'        => 0,
                'promotion'         => null,
                'in_stock'          => 1,
            ]);
            $out['product'][$p['code']] = [
                'id' => $pid, 'cost' => $p['cost'], 'price' => $p['price'],
                'unit' => $unitId, 'tax_id' => $taxId,
            ];

            // Opening stock rows (finalised later)
            $out['stock'][$pid] = [$whMain => 300.0, $whBranch => 80.0];
        }

        return $out;
    }

    /* ===================================================================== */
    /*  Parties (suppliers, customers, billers)                              */
    /* ===================================================================== */

    private function seedParties(): array
    {
        $out = ['supplier' => [], 'customer' => []];

        $cities = ['Springfield', 'Riverside', 'Fairview', 'Madison', 'Georgetown', 'Franklin', 'Clinton', 'Salem', 'Bristol', 'Ashland'];
        $countries = ['USA', 'Canada', 'United Kingdom'];

        $supplierNames = [
            'Atlas Wholesale Co.', 'Prime Distributors', 'Global Foods Supply', 'MetroMart Traders',
            'Sunrise Beverages Ltd', 'FreshLine Produce', 'TechSource Imports', 'HomeGoods Depot',
            'PaperWorks Supplies', 'Oceanic Seafoods', 'Golden Grain Bakery Supply', 'Everyday Essentials',
            'BlueSky Electronics', 'PureCare Products', 'FarmDirect Dairy', 'SnackWorld Distribution',
            'Summit Trading', 'Harbor Imports', 'GreenField Organics', 'Unity Wholesale Group',
        ];
        foreach ($supplierNames as $i => $name) {
            $company = str_replace([' Co.', ' Ltd', ' Inc'], '', $name);
            $out['supplier'][] = $this->ins('suppliers', [
                'name'         => $name,
                'image'        => null,
                'company_name' => $company,
                'vat_number'   => 'VAT' . (100000 + $i),
                'email'        => Str::slug($company, '') . '@supplier.demo',
                'phone_number' => '+1 ' . mt_rand(200, 989) . ' 555 ' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'address'      => mt_rand(10, 999) . ' ' . $this->pick(['Industrial', 'Commerce', 'Trade', 'Market', 'Harbor']) . ' Ave',
                'city'         => $this->pick($cities),
                'state'        => $this->pick(['CA', 'NY', 'TX', 'IL', 'WA']),
                'postal_code'  => (string) mt_rand(10000, 99999),
                'country'      => $this->pick($countries),
                'is_active'    => 1,
            ]);
        }

        $first = ['James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda', 'David', 'Elizabeth',
                  'William', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Sarah', 'Charles', 'Karen',
                  'Daniel', 'Nancy', 'Matthew', 'Lisa', 'Anthony', 'Betty', 'Mark', 'Sandra', 'Donald', 'Ashley'];
        $last  = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
                  'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
                  'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson'];

        $groups = [$this->firstId('customer_groups') ?? 1];
        foreach (DB::table('customer_groups')->pluck('id') as $g) {
            $groups[] = $g;
        }
        $groups = array_values(array_unique($groups));

        for ($i = 0; $i < 32; $i++) {
            $name = $first[$i % count($first)] . ' ' . $last[($i * 3) % count($last)];
            $out['customer'][] = $this->ins('customers', [
                'customer_group_id' => $this->pick($groups),
                'user_id'           => null,
                'name'              => $name,
                'company_name'      => mt_rand(0, 2) === 0 ? $last[($i * 3) % count($last)] . ' Enterprises' : null,
                'email'             => Str::slug($name, '.') . $i . '@customer.demo',
                'phone_number'      => '+1 ' . mt_rand(200, 989) . ' 555 ' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'address'           => mt_rand(10, 999) . ' ' . $this->pick(['Oak', 'Maple', 'Cedar', 'Pine', 'Elm', 'Main']) . ' St',
                'city'              => $this->pick($cities),
                'state'             => $this->pick(['CA', 'NY', 'TX', 'IL', 'WA']),
                'postal_code'       => (string) mt_rand(10000, 99999),
                'country'           => $this->pick($countries),
                'points'            => mt_rand(0, 500),
                'is_active'         => 1,
            ]);
        }

        return $out;
    }

    /* ===================================================================== */
    /*  Purchases                                                            */
    /* ===================================================================== */

    private function seedPurchases(array &$ids): void
    {
        $products = array_values($ids['product']);
        $suppliers = $ids['supplier'];
        $wh = $ids['warehouse_main'];

        for ($n = 0; $n < 28; $n++) {
            $when = $this->daysAgo(90);
            $lines = [];
            $count = mt_rand(3, 7);
            $codes = array_keys($ids['product']);
            $chosen = [];
            for ($k = 0; $k < $count; $k++) {
                $chosen[] = $codes[array_rand($codes)];
            }
            $chosen = array_unique($chosen);

            $totalQty = 0; $totalCost = 0; $totalTax = 0; $item = 0;
            foreach ($chosen as $code) {
                $p = $ids['product'][$code];
                $qty = mt_rand(20, 120);
                $cost = $p['cost'];
                $taxRate = 0;
                $lineTotal = $qty * $cost;
                $lines[] = [
                    'product_id' => $p['id'], 'qty' => $qty, 'recieved' => $qty,
                    'purchase_unit_id' => $p['unit'], 'net_unit_cost' => $cost,
                    'discount' => 0, 'tax_rate' => $taxRate, 'tax' => 0, 'total' => $lineTotal,
                ];
                $totalQty += $qty; $totalCost += $lineTotal; $item++;
                $ids['stock'][$p['id']][$wh] = ($ids['stock'][$p['id']][$wh] ?? 0) + $qty;
            }
            $grand = $totalCost + $totalTax;
            $paymentStatus = $this->pick([2, 2, 2, 1]); // 2 = paid, 1 = pending
            $paid = $paymentStatus === 2 ? $grand : 0;

            $purchaseId = $this->ins('purchases', [
                'reference_no' => $this->ref('pr-'),
                'user_id' => $ids['admin'],
                'warehouse_id' => $wh,
                'supplier_id' => $this->pick($suppliers),
                'item' => $item,
                'total_qty' => $totalQty,
                'total_discount' => 0,
                'total_tax' => $totalTax,
                'total_cost' => $totalCost,
                'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0,
                'grand_total' => $grand,
                'paid_amount' => $paid,
                'status' => 1,               // 1 = received
                'payment_status' => $paymentStatus,
                'document' => null,
                'note' => 'Demo purchase order',
                'created_at' => $when, 'updated_at' => $when,
            ]);

            foreach ($lines as $l) {
                $this->ins('product_purchases', array_merge($l, [
                    'purchase_id' => $purchaseId, 'created_at' => $when, 'updated_at' => $when,
                ]));
            }
            if ($paid > 0) {
                $this->ins('payments', [
                    'purchase_id' => $purchaseId,
                    'payment_reference' => $this->ref('ppr-'),
                    'amount' => $paid, 'change' => 0,
                    'paying_method' => $this->pick(['Cash', 'Bank Transfer', 'Cheque']),
                    'account_id' => $this->pick($ids['accounts']),
                    'user_id' => $ids['admin'],
                    'payment_note' => 'Supplier payment',
                    'created_at' => $when, 'updated_at' => $when,
                ]);
            }
        }
    }

    /* ===================================================================== */
    /*  Sales (POS + regular)                                                */
    /* ===================================================================== */

    private function seedSales(array &$ids): void
    {
        $codes = array_keys($ids['product']);
        $customers = array_merge([$this->firstId('customers') ?? 1], $ids['customer']);
        $wh = $ids['warehouse_main'];

        for ($n = 0; $n < 130; $n++) {
            $when = $this->daysAgo(90);
            $count = mt_rand(1, 5);
            $chosen = [];
            for ($k = 0; $k < $count; $k++) {
                $chosen[] = $codes[array_rand($codes)];
            }
            $chosen = array_unique($chosen);

            $totalQty = 0; $totalPrice = 0; $totalTax = 0; $item = 0;
            $lines = [];
            foreach ($chosen as $code) {
                $p = $ids['product'][$code];
                $avail = $ids['stock'][$p['id']][$wh] ?? 0;
                if ($avail < 1) { continue; }
                $qty = min($avail, mt_rand(1, 4));
                $price = $p['price'];
                $taxRate = 0;
                $lineTotal = $qty * $price;
                $lines[] = [
                    'product_id' => $p['id'], 'qty' => $qty, 'sale_unit_id' => $p['unit'],
                    'net_unit_price' => $price, 'discount' => 0, 'tax_rate' => $taxRate,
                    'tax' => 0, 'total' => $lineTotal,
                ];
                $totalQty += $qty; $totalPrice += $lineTotal; $item++;
                $ids['stock'][$p['id']][$wh] = $avail - $qty;
            }
            if ($item === 0) { continue; }

            $grand = round($totalPrice + $totalTax, 2);
            // payment mix: mostly paid, some partial, few pending
            $r = mt_rand(1, 10);
            if ($r <= 7)      { $paymentStatus = 4; $paid = $grand; }   // paid
            elseif ($r <= 9)  { $paymentStatus = 3; $paid = round($grand * 0.5, 2); } // partial
            else              { $paymentStatus = 1; $paid = 0; }        // pending

            $isPos = mt_rand(0, 1) === 1;
            $saleId = $this->ins('sales', [
                'reference_no' => $this->ref($isPos ? 'posr-' : 'sr-'),
                'user_id' => $ids['admin'],
                'customer_id' => $this->pick($customers),
                'warehouse_id' => $wh,
                'biller_id' => $ids['biller'],
                'item' => $item,
                'total_qty' => $totalQty,
                'total_discount' => 0,
                'total_tax' => $totalTax,
                'total_price' => $totalPrice,
                'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0,
                'grand_total' => $grand,
                'sale_status' => 1,          // 1 = completed
                'payment_status' => $paymentStatus,
                'paid_amount' => $paid,
                'document' => null,
                'sale_note' => null,
                'staff_note' => null,
                'created_at' => $when, 'updated_at' => $when,
            ]);

            foreach ($lines as $l) {
                $this->ins('product_sales', array_merge($l, [
                    'sale_id' => $saleId, 'created_at' => $when, 'updated_at' => $when,
                ]));
            }
            if ($paid > 0) {
                $this->ins('payments', [
                    'sale_id' => $saleId,
                    'payment_reference' => $this->ref('spr-'),
                    'amount' => $paid, 'change' => 0,
                    'paying_method' => $this->pick(['Cash', 'Card', 'Cash', 'Gift Card']),
                    'account_id' => $this->pick($ids['accounts']),
                    'user_id' => $ids['admin'],
                    'payment_note' => 'POS payment',
                    'created_at' => $when, 'updated_at' => $when,
                ]);
            }
        }
    }

    /* ===================================================================== */
    /*  Quotations                                                           */
    /* ===================================================================== */

    private function seedQuotations(array $ids): void
    {
        $codes = array_keys($ids['product']);
        $customers = array_merge([$this->firstId('customers') ?? 1], $ids['customer']);

        for ($n = 0; $n < 16; $n++) {
            $when = $this->daysAgo(60);
            $count = mt_rand(2, 5);
            $chosen = [];
            for ($k = 0; $k < $count; $k++) { $chosen[] = $codes[array_rand($codes)]; }
            $chosen = array_unique($chosen);

            $totalQty = 0; $totalPrice = 0; $item = 0; $lines = [];
            foreach ($chosen as $code) {
                $p = $ids['product'][$code];
                $qty = mt_rand(1, 10);
                $lineTotal = $qty * $p['price'];
                $lines[] = [
                    'product_id' => $p['id'], 'qty' => $qty, 'sale_unit_id' => $p['unit'],
                    'net_unit_price' => $p['price'], 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => $lineTotal,
                ];
                $totalQty += $qty; $totalPrice += $lineTotal; $item++;
            }
            $grand = $totalPrice;
            $qid = $this->ins('quotations', [
                'reference_no' => $this->ref('qr-'),
                'user_id' => $ids['admin'],
                'biller_id' => $ids['biller'],
                'customer_id' => $this->pick($customers),
                'warehouse_id' => $ids['warehouse_main'],
                'item' => $item,
                'total_qty' => $totalQty,
                'total_discount' => 0, 'total_tax' => 0, 'total_price' => $totalPrice,
                'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0,
                'grand_total' => $grand,
                'quotation_status' => $this->pick([1, 2, 3]), // pending / sent / accepted
                'note' => 'Demo quotation',
                'created_at' => $when, 'updated_at' => $when,
            ]);
            foreach ($lines as $l) {
                $this->ins('product_quotation', array_merge($l, [
                    'quotation_id' => $qid, 'created_at' => $when, 'updated_at' => $when,
                ]));
            }
        }
    }

    /* ===================================================================== */
    /*  Transfers (main → branch)                                            */
    /* ===================================================================== */

    private function seedTransfers(array &$ids): void
    {
        $codes = array_keys($ids['product']);
        $from = $ids['warehouse_main'];
        $to   = $ids['warehouse_branch'];

        for ($n = 0; $n < 9; $n++) {
            $when = $this->daysAgo(70);
            $count = mt_rand(2, 5);
            $chosen = [];
            for ($k = 0; $k < $count; $k++) { $chosen[] = $codes[array_rand($codes)]; }
            $chosen = array_unique($chosen);

            $totalQty = 0; $totalCost = 0; $item = 0; $lines = [];
            foreach ($chosen as $code) {
                $p = $ids['product'][$code];
                $avail = $ids['stock'][$p['id']][$from] ?? 0;
                $qty = min($avail, mt_rand(5, 25));
                if ($qty < 1) { continue; }
                $lineTotal = $qty * $p['cost'];
                $lines[] = [
                    'product_id' => $p['id'], 'qty' => $qty, 'purchase_unit_id' => $p['unit'],
                    'net_unit_cost' => $p['cost'], 'tax_rate' => 0, 'tax' => 0, 'total' => $lineTotal,
                ];
                $totalQty += $qty; $totalCost += $lineTotal; $item++;
                $ids['stock'][$p['id']][$from] = $avail - $qty;
                $ids['stock'][$p['id']][$to]   = ($ids['stock'][$p['id']][$to] ?? 0) + $qty;
            }
            if ($item === 0) { continue; }
            $tid = $this->ins('transfers', [
                'reference_no' => $this->ref('tr-'),
                'user_id' => $ids['admin'],
                'status' => 1,
                'from_warehouse_id' => $from,
                'to_warehouse_id' => $to,
                'item' => $item, 'total_qty' => $totalQty, 'total_tax' => 0,
                'total_cost' => $totalCost, 'grand_total' => $totalCost,
                'shipping_cost' => 0, 'note' => 'Demo stock transfer',
                'created_at' => $when, 'updated_at' => $when,
            ]);
            foreach ($lines as $l) {
                $this->ins('product_transfer', array_merge($l, [
                    'transfer_id' => $tid, 'created_at' => $when, 'updated_at' => $when,
                ]));
            }
        }
    }

    /* ===================================================================== */
    /*  Adjustments                                                          */
    /* ===================================================================== */

    private function seedAdjustments(array &$ids): void
    {
        $codes = array_keys($ids['product']);
        $wh = $ids['warehouse_main'];

        for ($n = 0; $n < 12; $n++) {
            $when = $this->daysAgo(50);
            $count = mt_rand(1, 4);
            $chosen = [];
            for ($k = 0; $k < $count; $k++) { $chosen[] = $codes[array_rand($codes)]; }
            $chosen = array_unique($chosen);

            $totalQty = 0; $item = 0; $lines = [];
            foreach ($chosen as $code) {
                $p = $ids['product'][$code];
                $action = $this->pick(['+', '-']);
                $qty = mt_rand(1, 8);
                $lines[] = ['product_id' => $p['id'], 'qty' => $qty, 'action' => $action];
                $totalQty += $qty; $item++;
                $cur = $ids['stock'][$p['id']][$wh] ?? 0;
                $ids['stock'][$p['id']][$wh] = $action === '+' ? $cur + $qty : max(0, $cur - $qty);
            }
            $aid = $this->ins('adjustments', [
                'reference_no' => $this->ref('adr-'),
                'user_id' => $ids['admin'],
                'warehouse_id' => $wh, 'total_qty' => $totalQty, 'item' => $item,
                'note' => $this->pick(['Stock count correction', 'Damaged goods', 'Expiry write-off', 'Found stock']),
                'created_at' => $when, 'updated_at' => $when,
            ]);
            foreach ($lines as $l) {
                $this->ins('product_adjustments', array_merge($l, [
                    'adjustment_id' => $aid, 'created_at' => $when, 'updated_at' => $when,
                ]));
            }
        }
    }

    /* ===================================================================== */
    /*  Sale returns                                                         */
    /* ===================================================================== */

    private function seedReturns(array &$ids): void
    {
        $sales = DB::table('sales')->where('payment_status', 4)->inRandomOrder()->limit(10)->get();
        $wh = $ids['warehouse_main'];

        foreach ($sales as $sale) {
            $saleProducts = DB::table('product_sales')->where('sale_id', $sale->id)->get();
            if ($saleProducts->isEmpty()) { continue; }
            $when = Carbon::parse($sale->created_at)->addDays(mt_rand(1, 10));
            if ($when->isFuture()) { $when = Carbon::now(); }

            $totalQty = 0; $totalPrice = 0; $item = 0; $lines = [];
            foreach ($saleProducts as $sp) {
                if (mt_rand(0, 1) === 0) { continue; }
                $qty = 1;
                $lineTotal = $qty * $sp->net_unit_price;
                $lines[] = [
                    'product_id' => $sp->product_id, 'qty' => $qty, 'sale_unit_id' => $sp->sale_unit_id,
                    'net_unit_price' => $sp->net_unit_price, 'discount' => 0, 'tax_rate' => 0,
                    'tax' => 0, 'total' => $lineTotal,
                ];
                $totalQty += $qty; $totalPrice += $lineTotal; $item++;
                $ids['stock'][$sp->product_id][$wh] = ($ids['stock'][$sp->product_id][$wh] ?? 0) + $qty;
            }
            if ($item === 0) {
                $sp = $saleProducts->first();
                $lineTotal = $sp->net_unit_price;
                $lines[] = [
                    'product_id' => $sp->product_id, 'qty' => 1, 'sale_unit_id' => $sp->sale_unit_id,
                    'net_unit_price' => $sp->net_unit_price, 'discount' => 0, 'tax_rate' => 0,
                    'tax' => 0, 'total' => $lineTotal,
                ];
                $totalQty = 1; $totalPrice = $lineTotal; $item = 1;
                $ids['stock'][$sp->product_id][$wh] = ($ids['stock'][$sp->product_id][$wh] ?? 0) + 1;
            }

            $rid = $this->ins('returns', [
                'reference_no' => $this->ref('rr-'),
                'user_id' => $ids['admin'],
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'warehouse_id' => $wh,
                'biller_id' => $ids['biller'],
                'account_id' => $ids['account_main'],
                'item' => $item, 'total_qty' => $totalQty,
                'total_discount' => 0, 'total_tax' => 0, 'total_price' => $totalPrice,
                'order_tax_rate' => 0, 'order_tax' => 0, 'grand_total' => $totalPrice,
                'return_note' => 'Customer return (demo)',
                'created_at' => $when, 'updated_at' => $when,
            ]);
            foreach ($lines as $l) {
                $this->ins('product_returns', array_merge($l, [
                    'return_id' => $rid, 'created_at' => $when, 'updated_at' => $when,
                ]));
            }
        }
    }

    /* ===================================================================== */
    /*  Accounting (expenses, incomes, transfers, coupons, gift cards)       */
    /* ===================================================================== */

    private function seedAccounting(array $ids): void
    {
        // Expense categories
        $expCats = [];
        foreach ([['RENT', 'Rent & Utilities'], ['SAL', 'Salaries'], ['MKT', 'Marketing'],
                  ['MNT', 'Maintenance'], ['SUP', 'Office Supplies'], ['TRN', 'Transport']] as $i => $c) {
            $expCats[] = $this->ins('expense_categories', ['code' => $c[0], 'name' => $c[1], 'is_active' => 1]);
        }
        for ($n = 0; $n < 34; $n++) {
            $when = $this->daysAgo(90);
            $this->ins('expenses', [
                'reference_no' => $this->ref('exp-'),
                'expense_category_id' => $this->pick($expCats),
                'warehouse_id' => $this->pick([$ids['warehouse_main'], $ids['warehouse_branch']]),
                'account_id' => $this->pick($ids['accounts']),
                'user_id' => $ids['admin'],
                'amount' => mt_rand(25, 1500) + mt_rand(0, 99) / 100,
                'note' => $this->pick(['Monthly rent', 'Electricity bill', 'Facebook ads', 'Equipment repair',
                                       'Printer paper & ink', 'Delivery fuel', 'Water bill', 'Cleaning service']),
                'created_at' => $when, 'updated_at' => $when,
            ]);
        }

        // Income categories
        $incCats = [];
        foreach ([['INT', 'Interest'], ['RNT', 'Sub-let Rent'], ['SCR', 'Scrap Sales'], ['MSC', 'Miscellaneous']] as $c) {
            $incCats[] = $this->ins('income_categories', ['code' => $c[0], 'name' => $c[1], 'is_active' => 1]);
        }
        for ($n = 0; $n < 16; $n++) {
            $when = $this->daysAgo(90);
            $this->ins('incomes', [
                'reference_no' => $this->ref('inc-'),
                'income_category_id' => $this->pick($incCats),
                'warehouse_id' => $this->pick([$ids['warehouse_main'], $ids['warehouse_branch']]),
                'account_id' => $this->pick($ids['accounts']),
                'user_id' => $ids['admin'],
                'amount' => mt_rand(50, 900) + mt_rand(0, 99) / 100,
                'note' => $this->pick(['Bank interest', 'Sub-let income', 'Scrap sale', 'Vending commission']),
                'created_at' => $when, 'updated_at' => $when,
            ]);
        }

        // Money transfers between accounts
        for ($n = 0; $n < 7; $n++) {
            $when = $this->daysAgo(80);
            $from = $this->pick($ids['accounts']);
            $to = $this->pick(array_values(array_diff($ids['accounts'], [$from])));
            $this->ins('money_transfers', [
                'reference_no' => $this->ref('mt-'),
                'from_account_id' => $from, 'to_account_id' => $to,
                'amount' => mt_rand(200, 5000),
                'note' => 'Internal fund transfer',
                'created_at' => $when, 'updated_at' => $when,
            ]);
        }

        // Coupons
        $coupons = [
            ['WELCOME10', 'percentage', 10], ['SAVE5', 'flat', 5], ['SUMMER15', 'percentage', 15],
            ['FREESHIP', 'flat', 8], ['VIP20', 'percentage', 20],
        ];
        foreach ($coupons as $c) {
            $this->ins('coupons', [
                'code' => $c[0], 'type' => $c[1], 'amount' => $c[2],
                'quantity' => mt_rand(50, 200), 'used' => mt_rand(0, 40),
                'minimum_amount' => mt_rand(0, 50),
                'expired_date' => Carbon::now()->addDays(mt_rand(20, 120))->toDateString(),
                'user_id' => $ids['admin'], 'is_active' => 1,
            ]);
        }

        // Gift cards
        $customers = array_merge([$this->firstId('customers') ?? 1], $ids['customer']);
        for ($n = 0; $n < 8; $n++) {
            $amount = $this->pick([25, 50, 100, 150, 200]);
            $this->ins('gift_cards', [
                'card_no' => 'GC' . str_pad((string) mt_rand(1, 99999), 6, '0', STR_PAD_LEFT),
                'amount' => $amount, 'expense' => mt_rand(0, (int) $amount),
                'customer_id' => $this->pick($customers),
                'user_id' => $ids['admin'], 'created_by' => $ids['admin'],
                'is_active' => 1,
                'expired_date' => Carbon::now()->addYear()->toDateString(),
                'created_at' => $this->daysAgo(60),
            ]);
        }
    }

    /* ===================================================================== */
    /*  HRM                                                                  */
    /* ===================================================================== */

    private function seedHrm(array $ids): void
    {
        // Departments
        $depts = [];
        foreach (['Sales', 'Warehouse', 'Finance', 'Customer Service', 'Management'] as $d) {
            $depts[$d] = $this->ins('departments', ['name' => $d, 'is_active' => 1]);
        }
        // Designations
        $desigs = [];
        foreach (['Cashier', 'Store Manager', 'Sales Associate', 'Stock Clerk', 'Accountant', 'Supervisor'] as $d) {
            $desigs[$d] = $this->ins('designations', ['name' => $d, 'is_active' => 1]);
        }
        // Shifts
        $shifts = [];
        $shifts[] = $this->ins('shifts', ['name' => 'Morning', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'is_active' => 1]);
        $shifts[] = $this->ins('shifts', ['name' => 'Evening', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'is_active' => 1]);
        $shifts[] = $this->ins('shifts', ['name' => 'Full Day', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_active' => 1]);

        // Leave types
        $leaveTypes = [];
        foreach ([['Annual Leave', 20], ['Sick Leave', 10], ['Casual Leave', 8], ['Unpaid Leave', 30]] as $lt) {
            $leaveTypes[] = $this->ins('leave_types', ['name' => $lt[0], 'days' => $lt[1], 'is_active' => 1]);
        }

        // Employees
        $first = ['Alex', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Riley', 'Jamie', 'Avery', 'Quinn', 'Drew',
                  'Sydney', 'Reese', 'Parker', 'Rowan', 'Skyler'];
        $last = ['Bennett', 'Coleman', 'Fisher', 'Grant', 'Hayes', 'Ingram', 'Kelly', 'Lane', 'Mason', 'Nolan',
                 'Owens', 'Pratt', 'Quincy', 'Reyes', 'Stone'];
        $employees = [];
        for ($i = 0; $i < 15; $i++) {
            $name = $first[$i] . ' ' . $last[$i];
            $employees[] = $this->ins('employees', [
                'name' => $name,
                'email' => Str::slug($name, '.') . '@staff.demo',
                'phone_number' => '+1 ' . mt_rand(200, 989) . ' 555 ' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'user_id' => null,
                'image' => null,
                'address' => mt_rand(10, 999) . ' Staff Rd',
                'city' => $this->pick(['Springfield', 'Riverside', 'Madison']),
                'country' => 'USA',
                'department_id' => $this->pick(array_values($depts)),
                'designation_id' => $this->pick(array_values($desigs)),
                'shift_id' => $this->pick($shifts),
                'warehouse_id' => $this->pick([$ids['warehouse_main'], $ids['warehouse_branch']]),
                'staff_id' => 'EMP' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'basic_salary' => mt_rand(1800, 5200),
                'is_active' => 1,
            ]);
        }

        // Attendance — last 21 days, weekdays
        foreach ($employees as $eid) {
            for ($d = 1; $d <= 21; $d++) {
                $day = Carbon::now()->subDays($d);
                if ($day->isWeekend()) { continue; }
                if (mt_rand(0, 9) === 0) { continue; } // occasional absence
                $inH = mt_rand(8, 9);
                $this->ins('attendances', [
                    'date' => $day->toDateString(),
                    'employee_id' => $eid,
                    'user_id' => $ids['admin'],
                    'checkin' => sprintf('%02d:%02d:00', $inH, mt_rand(0, 59)),
                    'checkout' => sprintf('%02d:%02d:00', mt_rand(16, 19), mt_rand(0, 59)),
                    'status' => 1,
                    'created_at' => $day, 'updated_at' => $day,
                ]);
            }
        }

        // Leaves
        for ($n = 0; $n < 10; $n++) {
            $start = Carbon::now()->subDays(mt_rand(-15, 40));
            $days = mt_rand(1, 5);
            $this->ins('leaves', [
                'employee_id' => $this->pick($employees),
                'leave_types' => $this->pick($leaveTypes),
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($days - 1)->toDateString(),
                'days' => $days,
                'reason' => $this->pick(['Family event', 'Medical', 'Vacation', 'Personal']),
                'status' => $this->pick(['pending', 'approved', 'approved']),
                'created_at' => Carbon::now()->subDays(mt_rand(1, 30)),
            ]);
        }

        // Holidays
        $holidays = [
            ["New Year's Day", '01-01'], ['Independence Day', '07-04'], ['Labor Day', '09-01'],
            ['Thanksgiving', '11-27'], ['Christmas Day', '12-25'], ['Company Anniversary', '06-15'],
        ];
        $year = date('Y');
        foreach ($holidays as $h) {
            $date = $year . '-' . $h[1];
            $this->ins('holidays', [
                'user_id' => $ids['admin'],
                'title' => $h[0],
                'from_date' => $date, 'to_date' => $date,
                'note' => 'Public holiday',
                'is_approved' => 1,
                'created_at' => Carbon::now()->subDays(mt_rand(30, 120)),
            ]);
        }

        // Payrolls
        foreach ($employees as $eid) {
            if (mt_rand(0, 2) === 0) { continue; }
            $when = Carbon::now()->subDays(mt_rand(1, 40));
            $amount = mt_rand(1800, 5200);
            $this->ins('payrolls', [
                'reference_no' => $this->ref('pay-'),
                'employee_id' => $eid,
                'account_id' => $this->pick($ids['accounts']),
                'user_id' => $ids['admin'],
                'amount' => $amount,
                'paying_method' => $this->pick(['Bank Transfer', 'Cash', 'Cheque']),
                'note' => 'Monthly salary',
                'created_at' => $when, 'updated_at' => $when,
            ]);
        }
    }

    /* ===================================================================== */
    /*  Add-ons (dining tables, couriers, deliveries)                        */
    /* ===================================================================== */

    private function seedAddons(array $ids): void
    {
        // Dining tables (Restaurant module — core `tables` table)
        if (Schema::hasTable('tables')) {
            for ($i = 1; $i <= 12; $i++) {
                $this->ins('tables', [
                    'name' => 'Table ' . $i,
                    'table_no' => (string) $i,
                    'number_of_person' => $this->pick([2, 2, 4, 4, 6, 8]),
                    'seat' => $this->pick([2, 4, 6, 8]),
                    'warehouse_id' => $ids['warehouse_main'],
                    'status' => 1,
                    'is_active' => 1,
                    'created_at' => Carbon::now()->subDays(mt_rand(10, 90)),
                ]);
            }
        }

        // Couriers
        $courierIds = [];
        if (Schema::hasTable('couriers')) {
            foreach ([['Swift Express', 'swift@courier.demo'], ['CityRunner', 'city@courier.demo'],
                      ['MetroDelivery', 'metro@courier.demo']] as $c) {
                $courierIds[] = $this->ins('couriers', [
                    'name' => $c[0], 'email' => $c[1],
                    'phone_number' => '+1 ' . mt_rand(200, 989) . ' 555 ' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                    'address' => mt_rand(10, 999) . ' Logistics Way',
                    'is_active' => 1,
                    'created_at' => Carbon::now()->subDays(mt_rand(10, 90)),
                ]);
            }
        }

        // Deliveries for a handful of completed sales
        if (Schema::hasTable('deliveries')) {
            $sales = DB::table('sales')->inRandomOrder()->limit(12)->get();
            foreach ($sales as $sale) {
                $when = Carbon::parse($sale->created_at);
                $this->ins('deliveries', [
                    'reference_no' => $this->ref('do-'),
                    'sale_id' => $sale->id,
                    'user_id' => $ids['admin'],
                    'courier_id' => $courierIds ? $this->pick($courierIds) : null,
                    'address' => mt_rand(10, 999) . ' Delivery St',
                    'status' => $this->pick([1, 2, 3]),
                    'delivered_by' => $this->pick(['Swift Express', 'CityRunner', 'In-house']),
                    'created_at' => $when, 'updated_at' => $when,
                ]);
            }
        }
    }

    /* ===================================================================== */
    /*  Finalise stock                                                       */
    /* ===================================================================== */

    private function finaliseStock(array $ids): void
    {
        foreach ($ids['stock'] as $productId => $byWarehouse) {
            $total = 0;
            foreach ($byWarehouse as $warehouseId => $qty) {
                $qty = max(0, round($qty, 2));
                $total += $qty;
                $exists = DB::table('product_warehouse')
                    ->where('product_id', (string) $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();
                if ($exists) {
                    DB::table('product_warehouse')
                        ->where('id', $exists->id)
                        ->update(['qty' => $qty]);
                } else {
                    $this->ins('product_warehouse', [
                        'product_id' => (string) $productId,
                        'warehouse_id' => $warehouseId,
                        'qty' => $qty,
                    ]);
                }
            }
            DB::table('products')->where('id', $productId)->update(['qty' => $total]);
        }
    }
}
