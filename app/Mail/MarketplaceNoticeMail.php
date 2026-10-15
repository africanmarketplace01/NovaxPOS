<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Plain notice to a marketplace vendor (fee hold, hold lifted, payout sent). */
class MarketplaceNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $lines,
        public array $facts,
        public string $actionUrl,
        public string $recipientName,
        public string $senderName,
        public string $senderEmail,
    ) {}

    public function build()
    {
        return $this->from($this->senderEmail, $this->senderName)
            ->subject($this->subjectLine)
            ->view('landlord.mail.marketplace-notice');
    }
}
