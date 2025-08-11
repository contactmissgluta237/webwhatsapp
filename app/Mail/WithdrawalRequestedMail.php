<?php

namespace App\Mail;

use App\Models\ExternalTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExternalTransaction $transaction) {}

    public function build()
    {
        return $this->subject('Nouvelle demande de retrait')
            ->markdown('emails.transactions.requested');
    }
}
