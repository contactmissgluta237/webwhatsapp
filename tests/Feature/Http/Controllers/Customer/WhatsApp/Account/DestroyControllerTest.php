<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Customer\WhatsApp\Account;

use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DestroyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test_session_customer',
            'session_name' => 'Customer Test Session',
        ]);
    }

    #[Test]
    public function it_deletes_account_successfully_when_nodejs_succeeds(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_customer' => Http::response([
                'success' => true,
                'sessionId' => 'test_session_customer',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('whatsapp.destroy', $this->account));

        $response->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test_session_customer')
                && $request->method() === 'DELETE';
        });
    }

    #[Test]
    public function it_shows_error_when_nodejs_connection_fails(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('whatsapp.destroy', $this->account));

        $response->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_denies_access_to_other_users_account(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->delete(route('whatsapp.destroy', $this->account));

        $response->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('error', 'Accès non autorisé à ce compte WhatsApp.');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->delete(route('whatsapp.destroy', $this->account));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }
}
