<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppMessageService;
use Exception;
use Illuminate\Console\Command;

class SendTestWhatsAppMessage extends Command
{
    protected $signature = 'whatsapp:send-test {phone} {message}';
    protected $description = 'Envoie un message de test WhatsApp';

    public function handle(WhatsAppMessageService $messageService): int
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');

        try {
            $sent = $messageService->sendMessage($phone, $message);

            if ($sent) {
                $this->info("✅ Message envoyé à {$phone}");

                return self::SUCCESS;
            }

            $this->error('❌ Échec envoi message');

            return self::FAILURE;

        } catch (Exception $e) {
            $this->error("Erreur: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
