<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Livewire\Customer\WhatsApp\CreateSession;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class CreateSessionLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_session_creation_saves_phone_number_from_node_response(): void
    {
        $sessionName = 'My Test Session';
        $sessionId = 'session_2_17562223624561_43f7dfe4';
        $phoneNumber = '23755332183';

        // Mock QR Service responses
        Http::fake([
            '*/api/sessions/create' => Http::response(['success' => true], 200),
            '*/api/sessions/*/qr' => Http::response(['qrCode' => 'test_qr_code_data'], 200),
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => $sessionId,
                'status' => 'connected',
                'phoneNumber' => $phoneNumber,
                'lastActivity' => '2025-08-26T15:32:42.476Z',
                'userId' => $this->user->id,
                'qrCode' => null,
            ], 200),
        ]);

        $component = Livewire::test(CreateSession::class)
            ->set('sessionName', $sessionName);

        // Generate QR Code
        $component->call('generateQRCode')
            ->assertSet('showQrSection', true);

        // Simulate QR scanning and connection check
        $component->call('confirmQRScanned');

        // Check that account was created with phone number and agent disabled by default
        $this->assertDatabaseHas('whatsapp_accounts', [
            'user_id' => $this->user->id,
            'session_name' => $sessionName,
            'phone_number' => $phoneNumber,
            'status' => 'connected',
            'agent_enabled' => false,
        ]);

        $account = WhatsAppAccount::where('user_id', $this->user->id)->first();
        $this->assertEquals($phoneNumber, $account->phone_number);
        $this->assertEquals($sessionName, $account->session_name);
        $this->assertFalse($account->agent_enabled, 'Agent should be disabled by default');
    }

    public function test_session_creation_handles_missing_phone_number(): void
    {
        $sessionName = 'Session Without Phone';
        $sessionId = 'session_no_phone_123';

        Http::fake([
            '*/api/sessions/create' => Http::response(['success' => true], 200),
            '*/api/sessions/*/qr' => Http::response(['qrCode' => 'test_qr_code_data'], 200),
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => $sessionId,
                'status' => 'connected',
                'userId' => $this->user->id,
                // No phoneNumber field
            ], 200),
        ]);

        $component = Livewire::test(CreateSession::class)
            ->set('sessionName', $sessionName);

        $component->call('generateQRCode');
        $component->call('confirmQRScanned');
        $component->call('checkConnectionStatus');

        // Check that account was created without phone number but agent disabled by default
        $this->assertDatabaseHas('whatsapp_accounts', [
            'user_id' => $this->user->id,
            'session_name' => $sessionName,
            'phone_number' => null,
            'status' => 'connected',
            'agent_enabled' => false,
        ]);

        $account = WhatsAppAccount::where('user_id', $this->user->id)->first();
        $this->assertFalse($account->agent_enabled, 'Agent should be disabled by default even without phone number');
    }

    public function test_connection_timeout_is_handled_properly(): void
    {
        $sessionName = 'Timeout Session';

        Http::fake([
            '*/api/sessions/create' => Http::response(['success' => true], 200),
            '*/api/sessions/*/qr' => Http::response(['qrCode' => 'test_qr_code_data'], 200),
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => 'test_session',
                'status' => 'connecting', // Never becomes connected
            ], 200),
        ]);

        $component = Livewire::test(CreateSession::class)
            ->set('sessionName', $sessionName);

        $component->call('generateQRCode');
        $component->call('confirmQRScanned');

        // Simulate timeout by setting max attempts
        $component->set('connectionAttempts', 60);
        $component->call('checkConnectionStatus');

        $component->assertSet('isWaitingConnection', false);

        // No account should be created
        $this->assertDatabaseMissing('whatsapp_accounts', [
            'user_id' => $this->user->id,
            'session_name' => $sessionName,
        ]);
    }

    public function test_session_name_with_spaces_is_allowed(): void
    {
        $sessionNameWithSpaces = 'My WhatsApp Agent With Spaces';

        Http::fake([
            '*/api/sessions/create' => Http::response(['success' => true], 200),
            '*/api/sessions/*/qr' => Http::response(['qrCode' => 'test_qr_code_data'], 200),
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => 'test_session',
                'status' => 'connected',
                'phoneNumber' => '123456789',
            ], 200),
        ]);

        $component = Livewire::test(CreateSession::class)
            ->set('sessionName', $sessionNameWithSpaces);

        $component->call('generateQRCode');
        $component->call('confirmQRScanned');
        $component->call('checkConnectionStatus');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'user_id' => $this->user->id,
            'session_name' => $sessionNameWithSpaces,
            'agent_enabled' => false,
        ]);

        $account = WhatsAppAccount::where('user_id', $this->user->id)->first();
        $this->assertFalse($account->agent_enabled, 'Agent should be disabled by default for sessions with spaces');
    }

    public function test_qr_generation_failure_is_handled(): void
    {
        Http::fake([
            '*/api/sessions/create' => Http::response(['success' => false, 'message' => 'Bridge error'], 400),
        ]);

        $component = Livewire::test(CreateSession::class)
            ->set('sessionName', 'Test Session');

        $component->call('generateQRCode')
            ->assertSet('showQrSection', false);
    }
}
