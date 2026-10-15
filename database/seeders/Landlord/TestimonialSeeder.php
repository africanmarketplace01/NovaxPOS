<?php

namespace Database\Seeders\Landlord;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'author_name'     => 'James Kowalski',
                'author_role'     => 'Owner, KW Electronics',
                'author_initials' => 'JK',
                'stars'           => 5,
                'body'            => 'NovaxPOS completely replaced our clunky legacy system. Setup took 20 minutes and our team was selling the same day. The inventory tracking alone saved us hours every week.',
                'is_active'       => true,
                'sort_order'      => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'author_name'     => 'Amara Mensah',
                'author_role'     => 'Director, Amara Fashion House',
                'author_initials' => 'AM',
                'stars'           => 5,
                'body'            => 'We run 3 branches and NovaxPOS handles them all from one account. The reports are beautiful and I can check performance on my phone from anywhere. Worth every penny.',
                'is_active'       => true,
                'sort_order'      => 2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'author_name'     => 'Sofia Ribeiro',
                'author_role'     => 'Manager, FreshBite Grocers',
                'author_initials' => 'SR',
                'stars'           => 5,
                'body'            => 'Switching from a spreadsheet to NovaxPOS felt like upgrading from a bicycle to a sports car. The POS is fast, the barcode scanning works perfectly, and support is incredible.',
                'is_active'       => true,
                'sort_order'      => 3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'author_name'     => 'Patrick Lund',
                'author_role'     => 'Founder, Nordic Supply Co.',
                'author_initials' => 'PL',
                'stars'           => 5,
                'body'            => 'The free trial convinced me on day one. Everything just works — invoices, purchase orders, customer accounts. I recommended it to every business owner I know.',
                'is_active'       => true,
                'sort_order'      => 4,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'author_name'     => 'Nadia Al-Hassan',
                'author_role'     => 'Pharmacist & Owner, Al-Hassan Pharma',
                'author_initials' => 'NA',
                'stars'           => 5,
                'body'            => 'As a pharmacy, compliance and batch tracking are critical. NovaxPOS handles product expiry dates and e-invoicing seamlessly. Our auditor was genuinely impressed.',
                'is_active'       => true,
                'sort_order'      => 5,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'author_name'     => 'Carlos Vega',
                'author_role'     => 'CEO, Riviera Retail Group',
                'author_initials' => 'CV',
                'stars'           => 5,
                'body'            => 'The multi-currency support is a game-changer for us. We serve tourists and NovaxPOS handles currency conversion and VAT calculations without any headaches.',
                'is_active'       => true,
                'sort_order'      => 6,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        DB::connection('saleprosaas_landlord')->table('landlord_testimonials')->insertOrIgnore($testimonials);

        $this->command->info('Testimonials seeded: 6 records.');
    }
}
