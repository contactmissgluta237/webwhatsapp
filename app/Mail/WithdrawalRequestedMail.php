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
        return $this->subject(__('emails.withdrawal_requested.subject'))
            ->markdown('emails.transactions.requested');
    }
}
