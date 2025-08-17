<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\WhatsAppAccountDisconnectedEvent;
use App\Mail\WhatsAppAccountDisconnectedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class WhatsAppAccountDisconnectedListener
{
    public function handle(WhatsAppAccountDisconnectedEvent $event): void
    {
        try {
            $account = $event->account;
            
            Log::info('WhatsApp account disconnected notification', [
                'account_id' => $account->id,
                'user_id' => $account->user_id,
                'session_name' => $account->session_name,
                'disconnected_at' => $account->last_disconnected_at,
            ]);

            Mail::to($account->user)->send(new WhatsAppAccountDisconnectedMail($account));
            
            Log::info('WhatsApp disconnection email sent', [
                'account_id' => $account->id,
                'user_email' => $account->user->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp disconnection notification', [
                'account_id' => $event->account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}