<?php

namespace App\Jobs;

use App\Models\landlord\MarketplaceOrder;
use App\Services\Marketplace\OrderNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMarketplaceOrderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(private int $orderId) {}

    public function handle(OrderNotifier $notifier): void
    {
        $order = MarketplaceOrder::find($this->orderId);
        if ($order) {
            $notifier->send($order);
        }
    }
}
