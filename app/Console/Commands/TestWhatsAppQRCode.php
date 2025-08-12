<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Console\Command;

class TestWhatsAppQRCode extends Command
{
    protected $signature = 'whatsapp:test-qr {--user-id=}';
    protected $description = 'Génère un QR code WhatsApp rapidement';

    public function handle(WhatsAppQRService $qrService): int
    {
        $userId = (int) ($this->option('user-id') ?: rand(100, 999));

        $this->info("📱 Génération QR code pour utilisateur: {$userId}");
        $this->info('⏳ Récupération QR...');
        // generate a unique session name
        $sessionName = 'test_'.uniqid();

        $result = $qrService->generateQRCode($sessionName, $userId);

        if ($result['success']) {
            $this->info('✅ QR généré !');
            $this->line("🔗 <fg=green>{$result['url']}</fg=green>");

            return self::SUCCESS;
        }

        $this->error("❌ {$result['error']}");

        return self::FAILURE;
    }
}
