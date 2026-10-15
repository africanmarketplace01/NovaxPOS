<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlatformFeeRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $feeRequest,     // stdClass row from platform_fee_requests
        public string $senderName,
        public string $senderEmail
    ) {}

    public function build()
    {
        return $this->from($this->senderEmail, $this->senderName)
            ->subject("Platform Fee Invoice {$this->feeRequest->reference} — {$this->feeRequest->currency} " . number_format((float) $this->feeRequest->fee_amount, 2))
            ->view('landlord.mail.platform-fee-request');
    }
}
