<?php

namespace App\Traits;

use App\Models\landlord\Tenant;
use Illuminate\Support\Str;
use DB;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\landlord\Package;
use App\Models\landlord\TenantPayment;
use App\Mail\TenantCreate;
use App\Models\landlord\MailSetting;
use Mail;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\Tenant\TenantDatabaseSeeder;
use Modules\Ecommerce\Database\Seeders\EcommerceDatabaseSeeder;
use Modules\Restaurant\Database\Seeders\RestaurantDatabaseSeeder;

trait TenantInfo
{

    use \App\Traits\MailInfo;

    public function getTenantId()
    {
        return tenant()->id;
    }

    public function features()
    {
        $features = [
            // ── POS Terminal (plan gate: sidebar_pos) ────────────────────────────
            'pos' => [
                'name'             => 'POS Terminal',
                'default'          => true,
                'permission_names' => 'sidebar_pos',
            ],

            // ── Products / Inventory ─────────────────────────────────────────────
            'product_and_categories' => [
                'name'    => 'Product and Categories',
                'default' => true,
                'permission_names' => 'sidebar_product,products-index,products-view,products-add,products-edit,products-delete,categories-index,categories-view,brand,unit,print_barcode,product_export',
            ],

            // ── Purchase + Sale + People ─────────────────────────────────────────
            'purchase_and_sale' => [
                'name'    => 'Purchase and Sale',
                'default' => true,
                'permission_names' => 'sidebar_sale,sidebar_purchase,sidebar_people,sales-index,sales-view,sales-add,sales-edit,sales-delete,sales-import,sale_export,change_sale_date,purchases-index,purchases-view,purchases-add,purchases-edit,purchases-delete,purchases-import,purchase_export,customers-index,customers-view,customer_export,suppliers-index,suppliers-view,billers-index,billers-view,billers-add,billers-edit,billers-delete,billers-import,gift_card,coupon,packing_slip_challan',
            ],

            // ── Online Store (SaaS marketplace / ecommerce storefront) ───────────
            'online_store' => [
                'name'             => 'Online Store',
                'default'          => false,
                'permission_names' => 'sidebar_online_store',
            ],

            // ── API Access (REST API tokens for integrations) ─────────────────
            'api_access' => [
                'name'             => 'API Access',
                'default'          => false,
                'permission_names' => 'sidebar_api_token',
            ],
            'sale_return' => [
                'name' => 'Sale Return',
                'default' => false,
                'permission_names' => 'returns-index,returns-view,returns-add,returns-edit,returns-delete'
            ],
            'purchase_return' => [
                'name' => 'Purchase Return',
                'default' => false,
                'permission_names' => 'purchase-return-index,purchase-return-view,purchase-return-add,purchase-return-edit,purchase-return-delete'
            ],
            'expense' => [
                'name' => 'Expense',
                'default' => false,
                'permission_names' => 'expenses-index,expenses-view,expenses-add,expenses-edit,expenses-delete,expense-categories,sidebar_expense'
            ],
            'income' => [
                'name' => 'Income',
                'default' => false,
                'permission_names' => 'incomes-index,incomes-view,incomes-add,incomes-edit,incomes-delete,income-categories,sidebar_income'
            ],
            'transfer' => [
                'name' => 'Stock Transfer',
                'default' => false,
                'permission_names' => 'transfers-index,transfers-view,transfers-add,transfers-edit,transfers-delete,transfers-import,sidebar_transfer'
            ],
            'quotation' => [
                'name' => 'Quotation',
                'default' => false,
                'permission_names' => 'quotes-index,quotes-view,quotes-add,quotes-edit,quotes-delete,sidebar_quotation'
            ],
            'delivery' => [
                'name' => 'Product Delivery',
                'default' => false,
                'permission_names' => 'delivery'
            ],
            'stock_count_and_adjustment' => [
                'name' => 'Stock Count and Adjustment',
                'default' => false,
                'permission_names' => 'stock_count,adjustment'
            ],
            'report' => [
                'name' => 'Report',
                'default' => false,
                'permission_names' => 'product-report,purchase-report,sale-report,customer-report,due-report,profit-loss,best-seller,daily-sale,monthly-sale,daily-purchase,monthly-purchase,payment-report,warehouse-stock-report,product-qty-alert,supplier-report,user-report,warehouse-report,product-expiry-report,sale-report-chart,dso-report,supplier-due-report,biller-report,sidebar_reports'
            ],
            'hrm' => [
                'name' => 'HRM',
                'default' => false,
                'permission_names' => 'hrm_setting,department,attendance,payroll,employees-index,employees-view,employees-add,employees-edit,employees-delete,holiday,sidebar_hrm,hrm-panel,sale-agents,designations,shift,overtime,leave-type,leave'
            ],
            'accounting' => [
                'name' => 'Accounting',
                'default' => false,
                'permission_names' => 'account-index,account-view,balance-sheet,account-statement,account-selection,money-transfer,sidebar_accounting,account-statement-permission,account-selection-permission'
            ],
            'whatsapp_cloud_api' => [
                'name' => 'WhatsApp Cloud API',
                'default' => false,
                'permission_names' => 'sidebar_whatsapp'
            ],
        ];

        if(file_exists(base_path('Modules/Manufacturing'))){
            $features['manufacturing'] = [
                'name' => 'Manufacturing',
                'default' => false,
                'permission_names' => 'sidebar_manufacturing,production-view,production-add,production-edit,production-delete,recipe-view,recipe-add,recipe-edit,recipe-delete'
            ];
        }
        if(file_exists(base_path('Modules/Ecommerce'))) {
            $features['ecommerce'] = [
                'name' => 'Ecommerce',
                'default' => false,
                'permission_names' => ''
            ];
        }
        if(file_exists(base_path('Modules/Woocommerce'))){
            $features['woocommerce'] = [
                'name' => 'Woocommerce',
                'default' => false,
                'permission_names' => ''
            ];
        }
        if(file_exists(base_path('Modules/Restaurant'))){
            $features['restaurant'] = [
                'name' => 'Restaurant',
                'default' => false,
                'permission_names' => ''
            ];
        }
        if(file_exists(base_path('app/Http/Controllers/Api'))){
            $features['api'] = [
                'name' => 'Mobile App',
                'default' => false,
                'permission_names' => ''
            ];
        }

        return $features;
    }

    //This function is called from tenantCheckout() in payment controller
    public function createTenant($request)
    {
        // general_settings is a TENANT table — when createTenant() runs it is called
        // from the landlord context where that table does not exist. Wrap in try/catch
        // so the null-guard fallback below is actually reached on the landlord DB.
        if (cache()->has('general_setting')) {
            $general_setting = cache()->get('general_setting');
        } else {
            try {
                $general_setting = DB::table('general_settings')->latest()->first();
            } catch (\Throwable $e) {
                $general_setting = null; // table doesn't exist in landlord DB — fall through to defaults
            }
        }
        // Null-guard: if general_settings table is empty or missing, use safe defaults
        $general_setting = $general_setting ?? (object)[
            'site_title'   => config('app.name', 'NovaxPOS'),
            'site_logo'    => null,
            'developed_by' => 'NovaxPOS',
            'email'        => config('mail.from.address', ''),
            'free_trial_limit' => 14,
        ];

        $package = Package::findOrFail($request->package_id);
        $packageFeatures = is_array($package->features)
            ? $package->features
            : json_decode($package->features, true) ?? [];
        $modules = [];
        if (in_array('manufacturing', $packageFeatures)) {
            $modules[] = 'manufacturing';
        }
        if (in_array('ecommerce', $packageFeatures)) {
            $modules[] = 'ecommerce';
        }
        if (in_array('woocommerce', $packageFeatures)) {
            $modules[] = 'woocommerce';
        }
        if (in_array('restaurant', $packageFeatures)) {
            $modules[] = 'restaurant';
        }
        if (in_array('api', $packageFeatures)) {
            $modules[] = 'api';
        }

        if (count($modules)) {
            $modules = implode(",", $modules);
        }
        else {
            $modules = Null;
        }

        if ($request->subscription_type == 'free_trial' || $request->subscription_type == 'free trial' || (!isset($request->created_by_admin) && $package->is_free_trial)) {
            $numberOfDaysToExpired = $general_setting->free_trial_limit ?? $package->trial_days ?? 14;
            $isLifetime = false;
        } elseif ($request->subscription_type == 'paid_trial') {
            // Trial period on a paid plan — active during trial, expires after trial_days
            $numberOfDaysToExpired = $package->trial_days ?? 14;
            $isLifetime = false;
        } elseif ($request->subscription_type == 'monthly') {
            $numberOfDaysToExpired = 30;
            $isLifetime = false;
        } elseif ($request->subscription_type == 'yearly') {
            $numberOfDaysToExpired = 365;
            $isLifetime = false;
        } elseif ($request->subscription_type == 'one_time' || $request->subscription_type == \App\Models\landlord\Tenant::MARKETPLACE_SUBSCRIPTION) {
            // One-off / lifetime plan, or a commission-only marketplace seller — never expires
            $numberOfDaysToExpired = 0;
            $isLifetime = true;
        } else {
            $numberOfDaysToExpired = 30; // safe fallback
            $isLifetime = false;
        }

        if (isset($request->payment_method)) {
            $paid_by = $request->payment_method;
        }
        else {
            $paid_by = '';
        }

        //////creating tenant start////////
        DB::beginTransaction();
        try {
            $tenant = Tenant::create([
                'id' => $request->tenant
            ]);

            $tenant->domains()->create([
                'domain' => $request->tenant . '.' . env('CENTRAL_DOMAIN')
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with(
                'not_permitted',
                __('db.failed_create_tenant'). ' - ' . $e->getMessage()
            );
        }
        //////creating tenant end/////////

        // ── Safety: ensure tenant database exists before running migrations ─────
        // Jobs\CreateDatabase (via TenantCreated event) already created the DB
        // automatically in Tenant::create() above. This IF NOT EXISTS guard is
        // belt-and-suspenders only — it is a no-op when CreateDatabase succeeded.
        $dbPrefix    = config('tenancy.database.prefix', 'tenant_');
        $dbSuffix    = config('tenancy.database.suffix', '');
        $tenantDbName = $dbPrefix . $request->tenant . $dbSuffix;

        try {
            // Use the landlord connection so we run as the configured DB user.
            DB::connection(config('tenancy.database.central_connection', 'mysql'))
                ->statement("CREATE DATABASE IF NOT EXISTS `{$tenantDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tenant DB create failed for ' . $request->tenant . ': ' . $e->getMessage());
            try { $tenant->domains()->delete(); $tenant->delete(); } catch (\Throwable $ce) {}
            return redirect()->back()->with(
                'not_permitted',
                'Failed to create tenant database: ' . $e->getMessage()
            );
        }

        // ── Run tenant migrations ──────────────────────────────────────────────
        try {
            $migrateExit = Artisan::call('tenants:migrate', [
                '--tenants' => $request->tenant,
                '--force'   => true,
            ]);
        } catch (\Throwable $e) {
            $migrateExit = 1;
            \Illuminate\Support\Facades\Log::error('Tenant migrate failed for ' . $request->tenant . ': ' . $e->getMessage());
        }

        if ($migrateExit !== 0) {
            $output = Artisan::output();
            \Illuminate\Support\Facades\Log::error('Tenant migrate non-zero exit for ' . $request->tenant . ': ' . $output);
            // Clean up the orphaned tenant record so the user can retry
            try { $tenant->domains()->delete(); $tenant->delete(); } catch (\Throwable $ce) {}
            return redirect()->back()->with(
                'not_permitted',
                'Tenant database migration failed. Please try again or contact support.'
            );
        }
        // ── End tenant database creation ────────────────────────────────────


        if ($paid_by) {
            TenantPayment::create([
                'tenant_id'       => $tenant->id,
                'amount'          => $request->price,
                'paid_by'         => $paid_by,
                'payment_proof'   => $request->payment_proof ?? null,
                'transaction_ref' => $request->transaction_ref ?? null,
            ]);
        }

        ///////////////Start if someone wants ecommerce demo as his own demo////////////////
        // if (isset($modules) && str_contains($modules, "ecommerce") && file_exists(public_path('ecommerce_demo.sql'))) {
        //     $tenant->run(function () {
        //         DB::unprepared(file_get_contents(public_path('ecommerce_demo.sql')));
        //     });
        // }
        ///////////////End if someone wants ecommerce demo as his own demo////////////////

        // Start set tenant specific data for TenantDatabaseSeeder
        $companyName = $request->company_name ?: ($request->name ?? $request->tenant);
        // Lifetime/one-time plans never expire; all others use calculated days
        $expiryDate  = $isLifetime ? null : date("Y-m-d", strtotime("+" . $numberOfDaysToExpired . " days"));

        // Prefer the tenant's own uploaded logo; fall back to platform logo
        $tenantOwnLogo = $request->logo_filename ?? null;

        $tenantData = [
            //set general_setting information
            'site_title'        => $companyName ?: ($general_setting->site_title ?? config('app.name', 'NovaxPOS')),
            'site_logo'         => $tenantOwnLogo ?? ($general_setting->site_logo ?? null),
            'package_id'        => $request->package_id,
            'subscription_type' => $request->subscription_type,
            'developed_by'      => $general_setting->developed_by ?? 'NovaxPOS',
            'modules'           => $modules,
            'expiry_date'       => $expiryDate,
            //set user information
            'name'              => $request->name,
            'email'             => $request->email,
            // Paid checkouts hash at register time and pass only the hash, via the
            // attributes bag so it can never be supplied as form input.
            'password'          => $request->attributes->get('password_hash') ?? bcrypt($request->password),
            'phone'             => $request->phone_number,
            'company_name'      => $companyName,
            //set package features
            'package_features'  => $packageFeatures,
        ];
        //End set tenant specific data for TenantDatabaseSeeder and call running TenantDatabaseSeeder

        //Start running TenantDatabaseSeeder
        TenantDatabaseSeeder::$tenantData = $tenantData;
        try {
            $seedExit = Artisan::call('tenants:seed', [
                '--tenants' => $request->tenant,
                '--force'   => true,
            ]);
        } catch (\Throwable $e) {
            $seedExit = 1;
            \Illuminate\Support\Facades\Log::error('Tenant seed failed for ' . $request->tenant . ': ' . $e->getMessage());
        }

        if ($seedExit !== 0) {
            $output = Artisan::output();
            \Illuminate\Support\Facades\Log::error('Tenant seed non-zero exit for ' . $request->tenant . ': ' . $output);
            try { $tenant->domains()->delete(); $tenant->delete(); } catch (\Throwable $ce) {}
            return redirect()->back()->with(
                'not_permitted',
                'Tenant database seeding failed. Please try again or contact support.'
            );
        }
        //End running TenantDatabaseSeeder

        // Copy site logo to tenant's public/logo/ — check new storage location first, then legacy public path
        $logoFilename = $general_setting->site_logo;
        if ($logoFilename) {
            $logoSrc = storage_path('app/landlord-logos/' . $logoFilename);
            if (!file_exists($logoSrc)) {
                $logoSrc = public_path('landlord/images/logo/' . $logoFilename);
            }
            if (file_exists($logoSrc)) {
                @copy($logoSrc, public_path('logo/' . $logoFilename));
            }
        }

        //Start running Ecommerce seeder for tenant if package has ecommerce module
        if (isset($modules) && str_contains($modules, 'ecommerce')) {
            Artisan::call('tenants:seed', [
                '--tenants' => $request->tenant,
                '--class' => EcommerceDatabaseSeeder::class,
                '--force' => true,
            ]);

            //Update slug column on category,brand,product table as this is needed for ecommerce
            $tenant->run(function () {
                $this->brandSlug();
                $this->categorySlug();
                $this->productSlug();

                DB::table('categories')->whereIn('id', [1, 6, 12, 23, 29, 30, 31, 33, 39])->update([
                    'icon' => DB::raw("
                                    CASE
                                        WHEN id = 1 THEN '20240117121500.png'
                                        WHEN id = 6 THEN '20240117121330.png'
                                        WHEN id = 12 THEN '20240117121400.png'
                                        WHEN id = 23 THEN '20240117121523.png'
                                        WHEN id = 29 THEN '20240117121304.png'
                                        WHEN id = 30 THEN '20240117121238.png'
                                        WHEN id = 31 THEN '20240117122452.png'
                                        WHEN id = 33 THEN '20240117121224.png'
                                        WHEN id = 39 THEN '20240204050037.png'
                                    END
                                ")
                ]);

                DB::table('products')->update(['is_online' => 1]);
            });

            copy(public_path("logo/") . $general_setting->site_logo, public_path("frontend/images/") . $general_setting->site_logo);
        }
        //End running Ecommerce seeder if package has ecommerce module

        //Start running Restaurant seeder for tenant if package has Restaurant module
        if (isset($modules) && str_contains($modules, 'restaurant')) {
            Artisan::call('tenants:seed', [
                '--tenants' => $request->tenant,
                '--class' => RestaurantDatabaseSeeder::class,
                '--force' => true,
            ]);
        }
        //End running Restaurant seeder if package has Restaurant module

        if (!env('WILDCARD_SUBDOMAIN')) {
            $this->addSubdomain($tenant);
        }

        // ── Copy tenant-supplied logo to public/logo/ ─────────────────────────
        $tenantLogoFilename = $request->logo_filename ?? null;
        if ($tenantLogoFilename) {
            $logoSrc = storage_path('app/landlord-logos/' . $tenantLogoFilename);
            if (file_exists($logoSrc)) {
                @copy($logoSrc, public_path('logo/' . $tenantLogoFilename));
            }
        }

        //updating tenant others information on landlord DB
        $tenant->update([
            'package_id'        => $request->package_id,
            'subscription_type' => $request->subscription_type,
            'company_name'      => $companyName,
            'phone_number'      => $request->phone_number,
            'email'             => $request->email,
            'expiry_date'       => $expiryDate,
            'logo'              => $tenantLogoFilename,
            // Manual payment pending → status=0 (inactive until superadmin activates)
            // paid_trial → status=1 (active during trial, no payment yet)
            // one_time / monthly / yearly / free_trial → status=1 after payment
            'status'            => !empty($request->pending_manual_payment) ? 0 : 1,
        ]);

        // ── Send welcome email to tenant (all paths) ──────────────────────────
        $mail_setting = MailSetting::latest()->first();
        if ($mail_setting) {
            try {
                $this->setMailInfo($mail_setting);
                $mail_data = [
                    'email'                   => $request->email,
                    'name'                    => $request->name,
                    'company_name'            => $companyName,
                    'subdomain'               => $request->tenant,
                    // Plain-text only when this request still has it (trials, admin-created).
                    // Paid checkouts keep just the hash, so the email shows the reset link instead.
                    'password'                => $request->attributes->has('password_hash') ? null : $request->password,
                    'superadmin_company_name' => $general_setting->site_title ?? config('app.name'),
                    'superadmin_email'        => $general_setting->email ?? config('mail.from.address'),
                    'login_url'               => 'http://' . $request->tenant . '.' . env('CENTRAL_DOMAIN') . '/login',
                    'subscription_type'       => $request->subscription_type ?? '',
                    'expiry_date'             => $expiryDate,
                ];
                Mail::to($mail_data['email'])->send(new TenantCreate($mail_data));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('[TenantCreate] Welcome email failed for ' . $request->tenant . ': ' . $e->getMessage());
            }
        }

        if (isset($request->created_by_admin)) {
            return redirect()->back()->with('message', 'Client created successfully.');
        }

        // Self-registration success: show the success page on the landlord domain
        // (auto-redirects to tenant login after countdown)
        $loginUrl = 'http://' . $request->tenant . '.' . env('CENTRAL_DOMAIN') . '/login';
        session()->flash('registration_success', [
            'login_url' => $loginUrl,
            'email'     => $request->email,
        ]);
        return redirect()->route('registration.success');
    }

    public function categorySlug()
    {
        Category::whereNull('slug')->each(function ($cat) {
            $cat->slug = Str::slug($cat->name, '-');
            $cat->save();
        });

        //Best approach for larger dataset
        // DB::table('categories')
        // ->whereNull('slug')
        // ->update(['slug' => DB::raw("REPLACE(LOWER(name), ' ', '-')")]);
    }

    public function brandSlug()
    {
        Brand::whereNull('slug')->each(function ($brand) {
            $brand->slug = Str::slug($brand->title, '-');
            $brand->save();
        });
    }

    public function productSlug()
    {
        Product::whereNull('slug')->each(function ($product) {
            $product->slug = Str::slug($product->name, '-');
            $product->save();
        });
    }

    public function changePermission($tenant, $packageFeatures, int $package_id, $expiry_date = null, $subscription_type = null)
    {
        $tenant->run(function () use ($tenant, $packageFeatures, $package_id, $expiry_date, $subscription_type) {

            $packageFeatures = json_decode($packageFeatures);
            $modules = [];
            if (in_array('manufacturing', $packageFeatures)) {
                $modules[] = 'manufacturing';
            }
            if (in_array('ecommerce', $packageFeatures)) {
                $modules[] = 'ecommerce';
            }
            if (in_array('woocommerce', $packageFeatures)) {
                $modules[] = 'woocommerce';
            }
            if (in_array('restaurant', $packageFeatures)) {
                $modules[] = 'restaurant';
            }
            if (in_array('api', $packageFeatures)) {
                $modules[] = 'api';
            }

            if (count($modules)) {
                $modules = implode(",", $modules);
            }
            else {
                $modules = Null;
            }

            $all_permissions_map = DB::table('permissions')->pluck('id', 'name')->toArray();
            $features = $this->features();

            $excluded_permission_names = [];

            // ৪. লজিক: প্যাকেজে নেই এমন ফিচারের পারমিশনগুলো বের করা (Exclude List)
            foreach ($features as $feature_key => $feature_data) {
                // যদি এই ফিচারটি বর্তমান প্যাকেজে না থাকে
                if (!in_array($feature_key, $packageFeatures)) {
                    if (!empty($feature_data['permission_names'])) {
                        $perms = explode(',', $feature_data['permission_names']);
                        foreach ($perms as $p) {
                            $excluded_permission_names[] = trim($p);
                        }
                    }
                }
            }

            // ২. মডিউল চেক করে addons হ্যান্ডেল করা
            if (empty($modules)) {
                $excluded_permission_names[] = 'addons';
            }

            $permission_ids_to_delete = [];
            $permission_ids_to_insert = [];

            // ৫. পারমিশন ফিল্টার করা (Delete vs Insert)
            foreach ($all_permissions_map as $perm_name => $perm_id) {
                if (in_array($perm_name, $excluded_permission_names)) {
                    // যদি excluded লিস্টে থাকে, তবে এটি ডিলিট হবে
                    $permission_ids_to_delete[] = $perm_id;
                } else {
                    // অন্যথায় এটি রোল-এ থাকতে হবে (Add)
                    $permission_ids_to_insert[] = $perm_id;
                }
            }

            // unnecessary permission delete for all roles
            if (!empty($permission_ids_to_delete)) {
                DB::table('role_has_permissions')
                    ->whereIn('permission_id', $permission_ids_to_delete)
                    ->delete();
            }

            // খ. নতুন পারমিশন ইনসার্ট করা (Bulk Insert for Optimization)
            // আগে চেক করি বর্তমানে রোলের কাছে কী কী পারমিশন আছে, যাতে ডুপ্লিকেট না হয়
            $existing_role_permissions = DB::table('role_has_permissions')
                ->where('role_id', 1)
                ->pluck('permission_id')
                ->toArray();

            $existingMap = array_flip($existing_role_permissions);

            $data_to_insert = [];

            foreach ($permission_ids_to_insert as $id) {
                if (!isset($existingMap[$id])) {
                    $data_to_insert[] = [
                        'permission_id' => $id,
                        'role_id'       => 1
                    ];
                }
            }

            if (!empty($data_to_insert)) {
                DB::table('role_has_permissions')->insert($data_to_insert);
            }

            $general_setting = \App\Models\GeneralSetting::latest()->first();
            $updateData = ['package_id' => $package_id, 'modules' => $modules];
            if ($expiry_date != null && $subscription_type != null) {
                $updateData['expiry_date'] = $expiry_date;
                $updateData['subscription_type'] = $subscription_type;
            }
            $general_setting->update($updateData);

            if (isset($modules) && str_contains($modules, 'ecommerce')) {
                Artisan::call('tenants:seed', [
                    '--tenants' => $tenant->id,
                    '--class' => EcommerceDatabaseSeeder::class,
                    '--force' => true,
                ]);

                $this->categorySlug();
                $this->brandSlug();
                $this->productSlug();

                if($general_setting->site_logo && file_exists(public_path("logo/") . $general_setting->site_logo)){
                    copy(public_path("logo/") . $general_setting->site_logo, public_path("frontend/images/") . $general_setting->site_logo);
                }
            }
        });
    }

    public function addSubdomain($tenant)
    {
        $subdomain = $tenant->id;
        if (env('SERVER_TYPE') == 'cpanel') {
            $url = "https://" . env('CENTRAL_DOMAIN') . ":2083/json-api/cpanel?cpanel_jsonapi_func=addsubdomain&cpanel_jsonapi_module=SubDomain&cpanel_jsonapi_version=2&domain=" . $subdomain . "&rootdomain=" . env('CENTRAL_DOMAIN');
            if (env('ROOT_DOMAIN'))
                $url .= "&dir=public_html";
            else
                $url .= "&dir=" . env('CENTRAL_DOMAIN');
            //return $url;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //setting the curl headers
            $headers = array(
                "Authorization: cpanel " . env('CPANEL_USER_NAME') . ":" . env('CPANEL_API_KEY'),
                "Content-Type: text/plain"
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            //for debug only!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            curl_exec($curl);
            curl_close($curl);
        } elseif (env('SERVER_TYPE') == 'plesk') {
            $host = env('CENTRAL_DOMAIN');
            $username = env('PLESK_USER_NAME');
            $password = env('PLESK_PASSWORD');
            $pleskApiUrl = 'https://' . $host . ':8443/api/v2/domains';
            $domainData = [
                'name' => $subdomain . '.' . $host,
                'hosting_type' => 'virtual',
                'hosting_settings' => [
                    'document_root' => '/httpdocs',
                ],
                'parent_domain' => [
                    "name" => $host,
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pleskApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($domainData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("$username:$password"),
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL hostname verification if needed
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL certificate verification if needed

            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response);
            $tenant->setInternal('domain_id', $response->id);
        }
    }

    public function deleteSubdomain($tenant)
    {
        if (env('SERVER_TYPE') == 'cpanel') {
            $subdomain = $tenant->id;
            $url = "https://" . env('CENTRAL_DOMAIN') . ":2083/json-api/cpanel?cpanel_jsonapi_func=delsubdomain&cpanel_jsonapi_module=SubDomain&cpanel_jsonapi_version=2&domain=" . $subdomain . "." . env('CENTRAL_DOMAIN');
            //return $url;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //setting the curl headers
            $headers = array(
                "Authorization: cpanel " . env('CPANEL_USER_NAME') . ":" . env('CPANEL_API_KEY'),
                "Content-Type: text/plain"
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            //for debug only!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $resp = curl_exec($curl);
            curl_close($curl);
        } elseif (env('SERVER_TYPE') == 'plesk') {
            $host = env('CENTRAL_DOMAIN');
            $username = env('PLESK_USER_NAME');
            $password = env('PLESK_PASSWORD');
            $domain_id = $tenant->getInternal('domain_id');
            $pleskApiUrl = 'https://' . $host . ':8443/api/v2/domains/' . $domain_id;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pleskApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("$username:$password"),
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL hostname verification if needed
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL certificate verification if needed
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
