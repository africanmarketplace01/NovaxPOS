<?php

namespace App\Console\Commands;

use App\Models\landlord\Tenant;
use App\Mail\TenantExpiredMail;
use App\Mail\TenantExpiringSoonMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessTenantExpiry extends Command
{
    protected $signature   = 'tenants:process-expiry';
    protected $description = 'Suspend expired tenants and send expiry warning emails.';

    public function handle(): int
    {
        if (!config('database.connections.saleprosaas_landlord')) {
            $this->info('SaaS mode not active. Skipping.');
            return self::SUCCESS;
        }

        // ── Suspend newly expired tenants ──────────────────────────────────
        $expired = Tenant::where('status', 1)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->get();

        foreach ($expired as $tenant) {
            $tenant->update(['status' => 0]);

            try {
                Mail::to($tenant->email)->send(new TenantExpiredMail($tenant));
            } catch (\Throwable $e) {
                Log::error("TenantExpiry mail failed for {$tenant->id}: " . $e->getMessage());
            }

            $this->info("Suspended: {$tenant->id} ({$tenant->email})");
        }

        // ── Warn tenants expiring in exactly 7 or 3 days ──────────────────
        foreach ([7, 3] as $days) {
            $expiring = Tenant::where('status', 1)
                ->whereDate('expiry_date', now()->addDays($days)->toDateString())
                ->get();

            foreach ($expiring as $tenant) {
                try {
                    Mail::to($tenant->email)->send(new TenantExpiringSoonMail($tenant, $days));
                } catch (\Throwable $e) {
                    Log::error("TenantExpiringSoon mail failed for {$tenant->id}: " . $e->getMessage());
                }

                $this->info("Warning sent ({$days} days): {$tenant->id}");
            }
        }

        $this->info('Done. Expired: ' . count($expired));
        return self::SUCCESS;
    }
}
