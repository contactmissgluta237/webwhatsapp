<?php

namespace Tests\Feature\Customer;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Livewire\Customer\Ticket\CreateTicketForm;
use App\Models\Geography\Country;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un pays par défaut pour les utilisateurs
        Country::create([
            'id' => 1,
            'name' => 'Cameroun',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
        ]);

        // Créer les permissions nécessaires
        $permissions = PermissionEnum::values();
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles nécessaires avec leurs permissions
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo(UserRole::CUSTOMER()->permissions());

        Storage::fake('public');
    }

    #[Test]
    public function customer_can_access_ticket_creation_page(): void
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);
        $customer->assignRole('customer');

        Wallet::create([
            'user_id' => $customer->id,
            'balance' => 1000.00,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.tickets.create'))
            ->assertOk()
            ->assertSee('Créer un ticket');
    }

    #[Test]
    public function customer_can_create_ticket_without_attachments(): void
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);
        $customer->assignRole('customer');

        Wallet::create([
            'user_id' => $customer->id,
            'balance' => 1000.00,
        ]);

        $this->actingAs($customer);

        Livewire::test(CreateTicketForm::class)
            ->set('title', 'Problème avec mon compte')
            ->set('description', 'Je ne peux pas accéder à mon dashboard')
            ->call('createTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Problème avec mon compte',
            'description' => 'Je ne peux pas accéder à mon dashboard',
            'user_id' => $customer->id,
        ]);
    }

    #[Test]
    public function customer_can_create_ticket_with_attachments(): void
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);
        $customer->assignRole('customer');

        Wallet::create([
            'user_id' => $customer->id,
            'balance' => 1000.00,
        ]);

        $this->actingAs($customer);

        $image = UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(1000);

        Livewire::test(CreateTicketForm::class)
            ->set('title', 'Bug avec interface')
            ->set('description', 'Voici une capture d\'écran du problème')
            ->set('attachments', [$image])
            ->call('createTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Bug avec interface',
            'description' => 'Voici une capture d\'écran du problème',
            'user_id' => $customer->id,
        ]);
    }

    #[Test]
    public function ticket_creation_validates_required_fields(): void
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);
        $customer->assignRole('customer');

        Wallet::create([
            'user_id' => $customer->id,
            'balance' => 1000.00,
        ]);

        $this->actingAs($customer);

        Livewire::test(CreateTicketForm::class)
            ->set('title', '')
            ->set('description', '')
            ->call('createTicket')
            ->assertHasErrors(['title', 'description']);
    }

    #[Test]
    public function guest_cannot_access_ticket_creation_page(): void
    {
        $this->get(route('customer.tickets.create'))
            ->assertRedirect('/login');
    }

    #[Test]
    public function admin_cannot_access_customer_ticket_creation_page(): void
    {
        $admin = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(PermissionEnum::values());
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customer.tickets.create'))
            ->assertForbidden();
    }
}
