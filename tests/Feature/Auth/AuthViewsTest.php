<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

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

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
        $this->user = $this->user->fresh();
    }

    #[Test]
    public function guest_can_access_login_view(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    #[Test]
    public function guest_can_access_register_view(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertViewIs('auth.register');
    }

    #[Test]
    public function guest_can_access_activate_account_view(): void
    {
        $this->get(route('account.activate'))
            ->assertOk()
            ->assertViewIs('auth.activate-account');
    }

    #[Test]
    public function guest_can_access_forgot_password_view(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('auth.forgot-password');
    }

    #[Test]
    public function guest_can_access_verify_otp_view(): void
    {
        $this->get(route('password.verify.otp'))
            ->assertOk()
            ->assertViewIs('auth.verify-otp');
    }

    #[Test]
    public function guest_can_access_reset_password_view(): void
    {
        $token = 'test-token-123';

        $this->get(route('password.reset', ['token' => $token]))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertViewHas('token', $token);
    }

    #[Test]
    public function guest_can_access_reset_password_phone_view(): void
    {
        $token = 'test-token-123';
        $identifier = '237655332183';
        $resetType = 'phone';

        $this->get(route('password.reset.phone', [
            'token' => $token,
            'identifier' => $identifier,
            'resetType' => $resetType,
        ]))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertViewHas('token', $token)
            ->assertViewHas('identifier', $identifier)
            ->assertViewHas('resetType', $resetType);
    }

    #[Test]
    public function authenticated_user_can_access_profile_redirect(): void
    {
        $this->actingAs($this->user)
            ->get(route('profile'))
            ->assertRedirect(); // Should redirect to appropriate profile based on role
    }

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $this->actingAs($this->user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    #[Test]
    public function authenticated_users_cannot_access_guest_auth_views(): void
    {
        $this->actingAs($this->user)
            ->get(route('login'))
            ->assertRedirect(); // Should redirect away from login

        $this->actingAs($this->user)
            ->get(route('register'))
            ->assertRedirect(); // Should redirect away from register
    }

    #[Test]
    public function guest_cannot_access_logout(): void
    {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_profile_redirect(): void
    {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    }
}