<?php

namespace Database\Seeders\Tenant;

use Database\Seeders\Tenant\BarcodeSeeder;
use Database\Seeders\Tenant\ExternalServicesSeeder;
use Database\Seeders\Tenant\LanguagesTableSeeder;
use Database\Seeders\Tenant\TranslationsTableSeeder;
use Database\Seeders\Tenant\ThemeSettingSeeder;
use Illuminate\Database\Seeder;
use App\Traits\TenantInfo;

use Illuminate\Support\Facades\DB;

class TenantDatabaseSeeder extends Seeder
{
    use TenantInfo;
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public static $tenantData = [];

    public function run()
    {
        $this->call(BarcodeSeeder::class);
        $this->call(ExternalServicesSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(TranslationsTableSeeder::class);

        // Always seed from hardcoded defaults.
        // The landlord DB does NOT have a theme_settings table — that table is
        // per-tenant only — so copying from central via tenancy()->central() fails.
        $this->call(ThemeSettingSeeder::class);

        if (!DB::table('invoice_settings')->count()) {
            $baseData = [
                'prefix'              => !empty(self::$tenantData) ? mb_substr(str_replace(' ', '', self::$tenantData['site_title']), 0, 10) : 'salepro',
                'number_of_digit'     => 4,
                'numbering_type'      => 'datewise',
                'start_number'        => 1000,
                'header_text'         => !empty(self::$tenantData) ? self::$tenantData['site_title'] : 'SalePro',
                'footer_text'         => 'Thank you for shopping with us',
                'footer_title'        => 'Thank you for shopping with us',
                'size'                => 'a4',
                'logo_height'         => 200,
                'logo_width'          => 200,
                'primary_color'       => '#ff0000',
                'text_color'          => '#000000',
                'invoice_date_format' => 'd.m.y h:i A',
                'is_default'          => 0,
                'status'              => 0,
                'show_column' => json_encode([
                    "is_default"            => 0,
                    "status"                => 0,
                    "show_barcode"          => 1,
                    "show_qr_code"          => 1,
                    "show_customer_details" => 1,
                    "show_shipping_details" => 1,
                    "show_payment_info"     => 1,
                    "show_discount"         => 1,
                    "show_tax_info"         => 1,
                    "show_description"      => 1,
                    "show_in_words"         => 1,
                    "active_primary_color"  => 0,
                    "active_text_color"     => 0,
                    "show_warehouse_info"   => 1,
                    "show_bill_to_info"     => 1,
                    "show_footer_text"      => 1,
                    "show_biller_info"      => 1,
                    "show_payment_note"     => 1,
                    "show_paid_info"        => 1,
                    "show_ref_number"       => 1,
                    "show_customer_name"    => 1,
                    "active_date_format"    => 0,
                    "active_generat_settings" => 0,
                    "active_logo_height_width" => 0,
                    "hide_total_due" => 0,
                ]),
            ];

            $templates = [
                'A4 Size Normal Invoice',
                '58mm Thermal Invoice',
                '80mm Thermal Invoice',
            ];

            $size = [
                'a4',
                '58mm',
                '80mm',
            ];

            $value = [
                1,
                0,
                0,
            ];

            foreach ($templates as $key => $name) {
                DB::table('invoice_settings')->insert(array_merge($baseData, [
                    'template_name' => $name,
                    'size' => $size[$key],
                    'is_default' => $value[$key]
                ]));
            }
        }

        if (!DB::table('general_settings')->count()) {
            DB::table('general_settings')->insert([
                [
                    'id' => 1,
                    'site_title' => !empty(self::$tenantData) ? self::$tenantData['site_title'] : 'SalePro',
                    'site_logo' => !empty(self::$tenantData) ? self::$tenantData['site_logo'] : '20250102042651.png',
                    'is_rtl' => 0,
                    'currency' => '1',
                    'package_id' => !empty(self::$tenantData) ? self::$tenantData['package_id'] : 0,
                    'subscription_type' => !empty(self::$tenantData) ? self::$tenantData['subscription_type'] : 'monthly',
                    'staff_access' => 'own',
                    'without_stock' => 'no',
                    'date_format' => 'd/m/Y',
                    'developed_by' => !empty(self::$tenantData) ? self::$tenantData['developed_by'] : 'Lioncoders',
                    'invoice_format' => 'standard',
                    'decimal' => 2,
                    'state' => 1,
                    'theme' => 'default.css',
                    'modules' => !empty(self::$tenantData) ? self::$tenantData['modules'] : NULL,
                    'currency_position' => 'prefix',
                    'expiry_date' => !empty(self::$tenantData) ? self::$tenantData['expiry_date'] : '1970-01-01',
                    'expiry_type' => 'days',
                    'expiry_value' => '0',
                    'is_zatca' => NULL,
                    'company_name' => NULL,
                    'vat_registration_number' => NULL,
                    'is_packing_slip' => 0,
                ]
            ]);
        }

        if (!DB::table('users')->count()) {
            DB::table('users')->insert([
                [
                    'id' => 1,
                    'name' => !empty(self::$tenantData) ? self::$tenantData['name'] : 'admin',
                    'email' => !empty(self::$tenantData) ? self::$tenantData['email'] : 'admin@gmail.com',
                    'password' => !empty(self::$tenantData) ? self::$tenantData['password'] : '$2y$10$DWAHTfjcvwCpOCXaJg11MOhsqns03uvlwiSUOQwkHL2YYrtrXPcL6',
                    'remember_token' => '6mN44MyRiQZfCi0QvFFIYAU9LXIUz9CdNIlrRS5Lg8wBoJmxVu8auzTP42ZW',
                    'phone' => !empty(self::$tenantData) ? self::$tenantData['phone'] : '12112',
                    'company_name' => !empty(self::$tenantData) ? self::$tenantData['company_name'] : 'lioncoders',
                    'role_id' => 1,
                    'biller_id' => NULL,
                    'warehouse_id' => NULL,
                    'is_active' => 1,
                    'is_deleted' => 0,
                ]
            ]);
        }

        if (!DB::table('roles')->count()) {
            DB::table('roles')->insert([
                [
                    'id' => 1,
                    'name' => 'Admin',
                    'description' => 'admin can access all data...',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 2,
                    'name' => 'Owner',
                    'description' => 'Staff of shop',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 4,
                    'name' => 'staff',
                    'description' => 'staff has specific acess...',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 5,
                    'name' => 'Customer',
                    'description' => NULL,
                    'is_active' => 1,
                    'guard_name' => 'web',
                ]
            ]);
        }

        // Link users to their Spatie role via the model_has_roles pivot —
        // Blade @can/@canany resolve user permissions through this table.
        try {
            $pivotUsers = DB::table('users')->whereNotNull('role_id')->where('is_deleted', 0)->get(['id', 'role_id']);
            foreach ($pivotUsers as $pivotUser) {
                if (DB::table('roles')->where('id', $pivotUser->role_id)->exists()) {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id'    => $pivotUser->role_id,
                        'model_type' => \App\Models\User::class,
                        'model_id'   => $pivotUser->id,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // model_has_roles missing on legacy schema — migration will create it
        }

        ///permissions table data insert start///
        $existing_permissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $existingMap = array_flip($existing_permissions);

        $permission_data = [
            [
                'name' => 'products-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'stock-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customer-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'due-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'profit-loss',
                'guard_name' => 'web',
            ],
            [
                'name' => 'best-seller',
                'guard_name' => 'web',
            ],
            [
                'name' => 'daily-sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monthly-sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'daily-purchase',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monthly-purchase',
                'guard_name' => 'web',
            ],
            [
                'name' => 'payment-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse-stock-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product-qty-alert',
                'guard_name' => 'web',
            ],
            [
                'name' => 'supplier-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'general_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'mail_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'pos_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'hrm_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'balance-sheet',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-statement',
                'guard_name' => 'web',
            ],
            [
                'name' => 'department',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attendance',
                'guard_name' => 'web',
            ],
            [
                'name' => 'payroll',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'user-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'stock_count',
                'guard_name' => 'web',
            ],
            [
                'name' => 'adjustment',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sms_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'create_sms',
                'guard_name' => 'web',
            ],
            [
                'name' => 'print_barcode',
                'guard_name' => 'web',
            ],
            [
                'name' => 'empty_database',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customer_group',
                'guard_name' => 'web',
            ],
            [
                'name' => 'unit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tax',
                'guard_name' => 'web',
            ],
            [
                'name' => 'gift_card',
                'guard_name' => 'web',
            ],
            [
                'name' => 'coupon',
                'guard_name' => 'web',
            ],
            [
                'name' => 'holiday',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse',
                'guard_name' => 'web',
            ],
            [
                'name' => 'brand',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'money-transfer',
                'guard_name' => 'web',
            ],
            [
                'name' => 'category',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delivery',
                'guard_name' => 'web',
            ],
            [
                'name' => 'send_notification',
                'guard_name' => 'web',
            ],
            [
                'name' => 'today_sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'today_profit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'currency',
                'guard_name' => 'web',
            ],
            [
                'name' => 'backup_database',
                'guard_name' => 'web',
            ],
            [
                'name' => 'reward_point_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'revenue_profit_summary',
                'guard_name' => 'web',
            ],
            [
                'name' => 'cash_flow',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monthly_summary',
                'guard_name' => 'web',
            ],
            [
                'name' => 'yearly_report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'discount_plan',
                'guard_name' => 'web',
            ],
            [
                'name' => 'discount',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product-expiry-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'all_notification',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-report-chart',
                'guard_name' => 'web',
            ],
            [
                'name' => 'dso-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product_history',
                'guard_name' => 'web',
            ],
            [
                'name' => 'supplier-due-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'custom_field',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'packing_slip_challan',
                'guard_name' => 'web',
            ],
            [
                'name' => 'biller-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'payment_gateway_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'barcode_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'language_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'addons',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-selection',
                'guard_name' => 'web',
            ],
            [
                'name' => 'invoice_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'invoice_create_edit_delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'handle_discount',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'role_permission',
                'guard_name' => 'web',
            ],
            [
                'name' => 'cart-product-update',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'change_sale_date',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_product',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_purchase',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_quotation',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_transfer',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_expense',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_income',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_accounting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_hrm',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_people',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_reports',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'designations',
                'guard_name' => 'web',
            ],
            [
                'name' => 'shift',
                'guard_name' => 'web',
            ],
            [
                'name' => 'overtime',
                'guard_name' => 'web',
            ],
            [
                'name' => 'leave-type',
                'guard_name' => 'web',
            ],
            [
                'name' => 'leave',
                'guard_name' => 'web',
            ],
            [
                'name' => 'hrm-panel',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-agents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customer_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_manufacturing',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_repair',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_whatsapp',
                'guard_name' => 'web',
            ],
            // Plan-based sidebar gating permissions
            [
                'name' => 'sidebar_pos',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_online_store',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_api_token',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'theme_settings',
                'guard_name' => 'web',
            ],
            [
                'name' => 'income-categories',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expense-categories',
                'guard_name' => 'web',
            ],
            [
                'name' => 'price_edit_in_sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'cost_edit_in_products',
                'guard_name' => 'web',
            ]
        ];

        $insertData = [];

        foreach ($permission_data as $row) {
            if (!isset($existingMap[$row['name']])) {
                $insertData[] = $row;
            }
        }

        if (!empty($insertData)) {
            DB::table('permissions')->insert($insertData);
        }
        ///permissions table data insert end///

        ///role_has_permissions table data insert start///
        $existing_role_permissions = DB::table('role_has_permissions')
            ->where('role_id', 1)
            ->pluck('permission_id')
            ->toArray();

        $existingMap = array_flip($existing_role_permissions);

        // ডাটাবেস থেকে সব পারমিশন নাম এবং আইডি নিয়ে আসা (Optimized Pluck method)
        $all_permissions_map = DB::table('permissions')->pluck('id', 'name')->toArray();
        $permissions_role = [];

        if(!config('database.connections.saleprosaas_landlord')) {
            // For salepro
            foreach ($all_permissions_map as $perm_name => $perm_id) {
                $permissions_role[] = [
                    'permission_id' => $perm_id,
                    'role_id'       => 1,
                ];
            }
        }
        else {
            $features = $this->features();
            // ১. লজিক সেট করা: টেন্যান্ট ডাটা থাকলে প্যাকেজ ফিচারগুলো এলাউড, না থাকলে কিছুই এলাউড না (Empty)
            $allowedFeatures = !empty(self::$tenantData) ? self::$tenantData['package_features'] : [];

            $excluded_permission_names = [];
            // $features লুপ করে অপ্রয়োজনীয় পারমিশনগুলো বের করছি
            foreach ($features as $feature_key => $feature_data) {
                // লজিক: যদি ফিচারটি $allowedFeatures এর মধ্যে না থাকে, তবে বাদ যাবে।
                // ব্যাখ্যা: যদি টেন্যান্ট ডাটা না থাকে, $allowedFeatures হবে ফাঁকা।
                // ফলে in_array সবসময় false দিবে এবং সব ফিচার-পারমিশন excluded লিস্টে ঢুকে যাবে।
                if (!in_array($feature_key, $allowedFeatures)) {
                    if (!empty($feature_data['permission_names'])) {
                        $perms = explode(',', $feature_data['permission_names']);
                        foreach ($perms as $p) {
                            $excluded_permission_names[] = trim($p);
                        }
                    }
                }
            }
            // ২. মডিউল চেক করে addons হ্যান্ডেল করা
            if (empty(self::$tenantData['modules'])) {
                $excluded_permission_names[] = 'addons';
            }
            // ম্যাপ থেকে চেক করে ডাটা সাজানো
            foreach ($all_permissions_map as $perm_name => $perm_id) {
                // যদি পারমিশনটির নাম excluded লিস্টে না থাকে, তবেই এটি যোগ হবে
                if (!in_array($perm_name, $excluded_permission_names)) {
                    $permissions_role[] = [
                        'permission_id' => $perm_id,
                        'role_id'       => 1,
                    ];
                }
            }
        }

        $insertData = [];

        foreach ($permissions_role as $row) {
            if (!isset($existingMap[$row['permission_id']])) {
                $insertData[] = $row;
            }
        }

        if (!empty($insertData)) {
            DB::table('role_has_permissions')->insert($insertData);
        }
        ///role_has_permissions table data insert end///

        if (!DB::table('accounts')->count()) {
            DB::table('accounts')->insert([
                [
                    'id' => 1,
                    'account_no' => '019912229',
                    'name' => 'Sales Account',
                    'initial_balance' => 0.0,
                    'total_balance' => 0.0,
                    'note' => 'This is the default account.',
                    'is_default' => 1,
                    'is_active' => 1,
                    'code' => NULL,
                    'type' => 'Bank Account',
                    'parent_account_id' => NULL,
                    'is_payment' => 1,
                ],
            ]);
        }

        if (!DB::table('billers')->count()) {
            DB::table('billers')->insert([
                [
                    'id' => 1,
                    'name' => 'Test Biller',
                    'image' => NULL,
                    'company_name' => 'Test Company',
                    'vat_number' => NULL,
                    'email' => 'test@gmail.com',
                    'phone_number' => '12312',
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('currencies')->count()) {
            DB::table('currencies')->insert([
                [
                    'id' => 1,
                    'name' => 'US Dollar',
                    'code' => 'USD',
                    'exchange_rate' => 1.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('customer_groups')->count()) {
            DB::table('customer_groups')->insert([
                [
                    'id' => 1,
                    'name' => 'General',
                    'percentage' => '0',
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('customers')->count()) {
            DB::table('customers')->insert([
                [
                    'id' => 1,
                    'customer_group_id' => 1,
                    'user_id' => NULL,
                    'name' => 'John Doe',
                    'company_name' => 'Test Company',
                    'email' => 'john@gmail.com',
                    'phone_number' => '231312',
                    'tax_no' => NULL,
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'points' => NULL,
                    'is_active' => 1,
                    'deposit' => NULL,
                    'expense' => NULL,
                ]
            ]);
        }

        if (!DB::table('pos_setting')->count()) {
            DB::table('pos_setting')->insert([
                [
                    'id' => 1,
                    'customer_id' => 1,
                    'warehouse_id' => 1,
                    'biller_id' => 1,
                    'product_number' => 2,
                    'keybord_active' => 1,
                    'is_table' => 0,
                    'send_sms' => 0,
                    'stripe_public_key' => NULL,
                    'stripe_secret_key' => NULL,
                    'paypal_live_api_username' => NULL,
                    'paypal_live_api_password' => NULL,
                    'paypal_live_api_secret' => NULL,
                    'payment_options' => 'cash,card,cheque,gift_card,deposit,paypal',
                    'invoice_option' => 'thermal',
                    'thermal_invoice_size' => '80',
                ]
            ]);
        }

        if (!DB::table('taxes')->count()) {
            DB::table('taxes')->insert([
                [
                    'id' => 1,
                    'name' => 'VAT 10%',
                    'rate' => 10.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('units')->count()) {
            DB::table('units')->insert([
                [
                    'id' => 1,
                    'unit_code' => 'Pc',
                    'unit_name' => 'piece',
                    'base_unit' => NULL,
                    'operator' => '*',
                    'operation_value' => 1.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('warehouses')->count()) {
            DB::table('warehouses')->insert([
                [
                    'id' => 1,
                    'name' => 'Default Warehouse',
                    'phone' => NULL,
                    'email' => NULL,
                    'address' => 'Test address',
                    'is_active' => 1,
                ]
            ]);
        }
    }
}
