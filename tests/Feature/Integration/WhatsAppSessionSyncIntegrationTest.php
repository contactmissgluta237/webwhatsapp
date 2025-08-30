<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Handlers\WhatsApp\WhatsAppSessionSyncHandler;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppSessionSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_demonstrates_complete_synchronization_workflow(): void
    {
        // Créer un utilisateur et un compte WhatsApp
        $user = User::factory()->create();
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'demo_session_sync',
            'session_name' => 'Demo Sync Session',
        ]);

        // Simuler une réponse réussie de Node.js
        Http::fake([
            'localhost:3000/api/sessions/demo_session_sync' => Http::response([
                'success' => true,
                'sessionId' => 'demo_session_sync',
                'message' => 'Session deleted successfully',
            ], 200),
        ]);

        // Instancier le service
        $syncService = new WhatsAppSessionSyncHandler(
            bridgeBaseUrl: 'http://localhost:3000',
            timeout: 30
        );

        // Vérifier que le compte existe avant la suppression
        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'session_id' => 'demo_session_sync',
        ]);

        // Effectuer la suppression synchronisée
        $syncService->deleteSessionSafely($account);

        // Vérifier que le compte a été supprimé de Laravel
        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $account->id,
        ]);

        // Vérifier que l'appel vers Node.js a été effectué
        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/sessions/demo_session_sync'
                && $request->method() === 'DELETE';
        });
    }

    #[Test]
    public function it_demonstrates_failure_handling_workflow(): void
    {
        // Créer un utilisateur et un compte WhatsApp
        $user = User::factory()->create();
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'demo_fail_session',
            'session_name' => 'Demo Fail Session',
        ]);

        // Simuler un échec de Node.js
        Http::fake([
            'localhost:3000/api/sessions/demo_fail_session' => Http::response([
                'success' => false,
                'message' => 'Session not found or cannot be deleted',
            ], 500),
        ]);

        // Instancier le service
        $syncService = new WhatsAppSessionSyncHandler(
            bridgeBaseUrl: 'http://localhost:3000',
            timeout: 30
        );

        // Vérifier que le compte existe avant la tentative
        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'session_id' => 'demo_fail_session',
        ]);

        // Tenter la suppression (doit échouer)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur lors de la suppression de la session WhatsApp');

        try {
            $syncService->deleteSessionSafely($account);
        } catch (\Exception $e) {
            // Le compte doit toujours exister car Node.js a échoué
            $this->assertDatabaseHas('whatsapp_accounts', [
                'id' => $account->id,
                'session_id' => 'demo_fail_session',
            ]);

            throw $e;
        }
    }
}
