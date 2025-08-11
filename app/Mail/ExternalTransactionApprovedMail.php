<?php

namespace App\Mail;

use App\Models\ExternalTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExternalTransactionApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExternalTransaction $transaction) {}

    public function build()
    {
        return $this->subject('Approbation de votre demande de retrait')
            ->view('emails.transactions.approved-custom')
            ->with([
                'transaction' => $this->transaction,
            ]);
    }
}
