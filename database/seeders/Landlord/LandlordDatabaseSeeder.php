<?php

namespace Database\Seeders\Landlord;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default superadmin account
        DB::connection('saleprosaas_landlord')->table('superadmin_users')->insertOrIgnore([
            'name'       => 'Super Admin',
            'email'      => 'admin@novaxpos.com',
            'password'   => Hash::make('admin@12345'),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default subscription packages
        $packages = [
            [
                'name'             => 'Free Trial',
                'description'      => 'Try NovaxPOS free for 30 days with all basic features.',
                'price_monthly'    => 0,
                'price_yearly'     => 0,
                'features'         => json_encode(['product_and_categories', 'purchase_and_sale', 'report']),
                'product_limit'    => 100,
                'user_limit'       => 2,
                'warehouse_limit'  => 1,
                'is_free_trial'    => true,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 0,
                'badge'            => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Starter',
                'description'      => 'Perfect for small businesses getting started.',
                'price_monthly'    => 19.99,
                'price_yearly'     => 199.99,
                'features'         => json_encode([
                    'product_and_categories', 'purchase_and_sale', 'sale_return',
                    'purchase_return', 'expense', 'report',
                ]),
                'product_limit'    => 500,
                'user_limit'       => 3,
                'warehouse_limit'  => 1,
                'is_free_trial'    => false,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 1,
                'badge'            => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Professional',
                'description'      => 'For growing businesses with advanced needs.',
                'price_monthly'    => 49.99,
                'price_yearly'     => 499.99,
                'features'         => json_encode([
                    'product_and_categories', 'purchase_and_sale', 'sale_return',
                    'purchase_return', 'expense', 'income', 'transfer', 'quotation',
                    'delivery', 'stock_count_and_adjustment', 'report', 'accounting',
                ]),
                'product_limit'    => 5000,
                'user_limit'       => 10,
                'warehouse_limit'  => 5,
                'is_free_trial'    => false,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 2,
                'badge'            => 'Most Popular',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Enterprise',
                'description'      => 'Unlimited power for large-scale operations.',
                'price_monthly'    => 99.99,
                'price_yearly'     => 999.99,
                'features'         => json_encode([
                    'product_and_categories', 'purchase_and_sale', 'sale_return',
                    'purchase_return', 'expense', 'income', 'transfer', 'quotation',
                    'delivery', 'stock_count_and_adjustment', 'report', 'hrm',
                    'accounting', 'whatsapp_cloud_api', 'manufacturing', 'api',
                ]),
                'product_limit'    => null,
                'user_limit'       => null,
                'warehouse_limit'  => null,
                'is_free_trial'    => false,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 3,
                'badge'            => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ];

        DB::connection('saleprosaas_landlord')->table('packages')->insertOrIgnore($packages);

        $this->command->info('Landlord database seeded: superadmin + 4 default packages.');
    }
}
