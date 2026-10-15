<?php

namespace App\Mail;

use App\Models\landlord\MarketplaceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketplaceOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MarketplaceOrder $order,
        public string $recipientRole,   // vendor | agency | superadmin
        public array  $items,           // items relevant to this recipient
        public string $senderName,
        public string $senderEmail,
        public ?string $recipientName = null
    ) {}

    public function build()
    {
        $subjects = [
            'vendor'     => "New Marketplace Order — {$this->order->order_number}",
            'agency'     => "New Delivery Request — {$this->order->order_number}",
            'superadmin' => "Marketplace Order Placed — {$this->order->order_number}",
        ];

        return $this->from($this->senderEmail, $this->senderName)
            ->subject($subjects[$this->recipientRole] ?? $subjects['superadmin'])
            ->view('landlord.mail.marketplace-order');
    }
}
