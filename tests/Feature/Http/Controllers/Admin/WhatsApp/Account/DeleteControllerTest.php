<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\WhatsApp\Account;

use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeleteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $regularUser = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $regularUser->id,
            'session_id' => 'test_session_admin',
            'session_name' => 'Admin Test Session',
        ]);
    }

    #[Test]
    public function it_deletes_account_successfully_when_nodejs_succeeds(): void
    {
        Http::fake([
            'localhost:3000/api/sessions/test_session_admin' => Http::response([
                'success' => true,
                'sessionId' => 'test_session_admin',
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.whatsapp.accounts.destroy', $this->account));

        $response->assertRedirect(route('admin.whatsapp.accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test_session_admin')
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

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.whatsapp.accounts.destroy', $this->account));

        $response->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_requires_admin_authentication(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('customer');

        $response = $this->actingAs($regularUser)
            ->delete(route('admin.whatsapp.accounts.destroy', $this->account));

        $response->assertStatus(403);

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->account->id,
        ]);
    }
}
