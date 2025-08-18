<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Créer un pays pour éviter les erreurs de validation
        \Illuminate\Support\Facades\DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin = $this->admin->fresh();

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        $this->customer = $this->customer->fresh();
    }

    #[Test]
    public function authenticated_user_can_access_whatsapp_index(): void
    {
        $this->actingAs($this->customer)
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertViewIs('whatsapp.index')
            ->assertViewHas('sessions');
    }

    #[Test]
    public function authenticated_user_can_access_whatsapp_create(): void
    {
        $this->actingAs($this->customer)
            ->get(route('whatsapp.create'))
            ->assertOk()
            ->assertViewIs('whatsapp.create');
    }

    #[Test]
    public function authenticated_user_can_access_configure_ai_page(): void
    {
        // Créer un compte WhatsApp pour tester
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('whatsapp.configure-ai', $account))
            ->assertOk()
            ->assertViewIs('whatsapp.configure-ai')
            ->assertViewHas('account', $account);
    }

    #[Test]
    public function user_can_only_access_own_whatsapp_accounts(): void
    {
        // Créer un compte WhatsApp appartenant à un autre utilisateur
        $otherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('whatsapp.configure-ai', $otherAccount))
            ->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_toggle_ai(): void
    {
        // Créer un compte WhatsApp pour tester
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'ai_enabled' => false,
        ]);

        $this->actingAs($this->customer)
            ->post(route('whatsapp.toggle-ai', $account))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Vérifier que l'état AI a changé
        $this->assertTrue($account->fresh()->ai_enabled);
    }

    #[Test]
    public function authenticated_user_can_delete_own_whatsapp_account(): void
    {
        // Créer un compte WhatsApp pour tester
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->customer)
            ->delete(route('whatsapp.destroy', $account))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Vérifier que le compte a été supprimé
        $this->assertDatabaseMissing('whatsapp_accounts', ['id' => $account->id]);
    }

    #[Test]
    public function user_cannot_delete_other_users_whatsapp_accounts(): void
    {
        // Créer un compte WhatsApp appartenant à un autre utilisateur
        $otherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->customer)
            ->delete(route('whatsapp.destroy', $otherAccount))
            ->assertForbidden();

        // Vérifier que le compte n'a pas été supprimé
        $this->assertDatabaseHas('whatsapp_accounts', ['id' => $otherAccount->id]);
    }

    #[Test]
    public function guest_cannot_access_whatsapp_endpoints(): void
    {
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->get(route('whatsapp.index'))
            ->assertRedirect(route('login'));

        $this->get(route('whatsapp.create'))
            ->assertRedirect(route('login'));

        $this->get(route('whatsapp.configure-ai', $account))
            ->assertRedirect(route('login'));

        $this->post(route('whatsapp.toggle-ai', $account))
            ->assertRedirect(route('login'));

        $this->delete(route('whatsapp.destroy', $account))
            ->assertRedirect(route('login'));
    }
}