<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateLandlord extends Command
{
    protected $signature   = 'landlord:migrate {--fresh : Drop all tables and re-migrate} {--seed : Seed after migration}';
    protected $description = 'Run migrations for the landlord (central) database';

    public function handle(): int
    {
        if (!config('database.connections.saleprosaas_landlord')) {
            $this->error('LANDLORD_DB is not set in .env. Please configure it first.');
            return 1;
        }

        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        $this->info('Running landlord migrations...');

        Artisan::call($command, [
            '--database' => 'saleprosaas_landlord',
            '--path'     => 'database/migrations/landlord',
            '--force'    => true,
        ], $this->output);

        if ($this->option('seed')) {
            $this->info('Seeding landlord database...');
            Artisan::call('db:seed', [
                '--class'    => \Database\Seeders\Landlord\LandlordDatabaseSeeder::class,
                '--database' => 'saleprosaas_landlord',
                '--force'    => true,
            ], $this->output);
        }

        $this->info('Landlord migration complete.');
        return 0;
    }
}
