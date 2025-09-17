<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReferralsManagementTest extends TestCase
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
    public function admin_can_access_referrals_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertViewIs('admin.referrals.index');
    }

    #[Test]
    public function customer_cannot_access_admin_referrals(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.referrals.index'))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_admin_referrals(): void
    {
        $this->get(route('admin.referrals.index'))
            ->assertRedirect(route('login'));
    }
}