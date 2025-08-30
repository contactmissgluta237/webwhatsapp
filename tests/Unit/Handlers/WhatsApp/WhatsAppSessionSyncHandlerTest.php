<?php

declare(strict_types=1);

namespace Tests\Unit\Handlers\WhatsApp;

use App\Handlers\WhatsApp\WhatsAppSessionSyncHandler;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppSessionSyncHandlerTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppSessionSyncHandler $service;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new WhatsAppSessionSyncHandler(
            bridgeBaseUrl: 'http://localhost:3000',
            timeout: 30
        );

        $user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_123',
            'session_name' => 'Test Session',
        ]);
    }

    #[Test]
    public function it_deletes_session_safely_when_nodejs_succeeds(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_123' => Http::response([
                'success' => true,
                'sessionId' => 'test_session_123',
            ], 200),
        ]);

        $this->service->deleteSessionSafely($this->account);

        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/sessions/test_session_123'
                && $request->method() === 'DELETE';
        });
    }

    #[Test]
    public function it_proceeds_when_nodejs_returns_404(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_123' => Http::response([
                'success' => false,
                'message' => 'Session not found',
            ], 404),
        ]);

        $this->service->deleteSessionSafely($this->account);

        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_throws_exception_when_nodejs_connection_fails(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_123' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Impossible de contacter le service WhatsApp');

        $this->service->deleteSessionSafely($this->account);

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_throws_exception_when_nodejs_returns_server_error(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_123' => Http::response([
                'success' => false,
                'message' => 'Internal server error',
            ], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur lors de la suppression de la session WhatsApp');

        $this->service->deleteSessionSafely($this->account);

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_does_not_delete_laravel_session_if_nodejs_fails(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_123' => Http::response([
                'success' => false,
                'message' => 'Session deletion failed',
            ], 500),
        ]);

        try {
            $this->service->deleteSessionSafely($this->account);
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
            'session_id' => 'test_session_123',
        ]);
    }
}
