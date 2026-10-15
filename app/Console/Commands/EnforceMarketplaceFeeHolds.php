<?php

namespace App\Console\Commands;

use App\Services\Marketplace\FeeHolds;
use Illuminate\Console\Command;

/**
 * Hides marketplace sellers' listings while a platform-fee invoice is overdue
 * and restores them once it is settled. Scheduled daily; safe to run any time.
 */
class EnforceMarketplaceFeeHolds extends Command
{
    protected $signature = 'marketplace:enforce-fee-holds';

    protected $description = 'Hide or restore marketplace sellers\' listings based on overdue platform-fee invoices';

    public function handle(): int
    {
        $result = FeeHolds::syncAll();
        $this->info("Checked {$result['checked']} seller(s); {$result['held']} on hold.");

        return self::SUCCESS;
    }
}
