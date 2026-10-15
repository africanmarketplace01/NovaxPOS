<?php

namespace App\Mail;

use App\Models\landlord\TenantContract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantContractMail extends Mailable
{
    use Queueable, SerializesModels;

    public TenantContract $contract;
    public string         $senderName;
    public string         $senderEmail;

    public function __construct(TenantContract $contract, string $senderName, string $senderEmail)
    {
        $this->contract    = $contract;
        $this->senderName  = $senderName;
        $this->senderEmail = $senderEmail;
    }

    public function build(): static
    {
        $mail = $this
            ->subject($this->contract->getDocumentTypeLabel() . ' — ' . ($this->contract->tenant->company_name ?? $this->contract->tenant_id))
            ->view('landlord.mail.tenant_contract', [
                'contract'    => $this->contract,
                'senderName'  => $this->senderName,
                'senderEmail' => $this->senderEmail,
            ]);

        // Attach the PDF if it has been generated
        if ($this->contract->pdf_path && file_exists(storage_path('app/' . $this->contract->pdf_path))) {
            $mail->attach(storage_path('app/' . $this->contract->pdf_path), [
                'as'   => str_replace(' ', '_', $this->contract->getDocumentTypeLabel()) . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
