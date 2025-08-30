<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Customer\WhatsApp;

use App\Livewire\Customer\WhatsApp\NotificationConfig;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationConfigTest extends TestCase
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
            'agent_name' => 'Test Business',
        ]);

        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_mount_component_with_account(): void
    {
        $component = Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->assertSet('account.id', $this->account->id)
            ->assertSet('enableEmailNotifications', false)
            ->assertSet('notificationEmail', null)
            ->assertSet('enableWhatsappNotifications', false)
            ->assertSet('notificationWhatsappNumber', null);

        $this->assertNull($component->get('settings'));
    }

    #[Test]
    public function it_loads_existing_settings_on_mount(): void
    {
        $settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);

        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->assertSet('enableEmailNotifications', true)
            ->assertSet('notificationEmail', 'admin@example.com')
            ->assertSet('enableWhatsappNotifications', true)
            ->assertSet('notificationWhatsappNumber', '+33612345678')
            ->assertSet('settings.id', $settings->id);
    }

    #[Test]
    public function it_can_enable_email_notifications(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->assertSet('enableEmailNotifications', true);
    }

    #[Test]
    public function it_clears_email_when_disabling_email_notifications(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'test@example.com')
            ->set('enableEmailNotifications', false)
            ->assertSet('enableEmailNotifications', false)
            ->assertSet('notificationEmail', null);
    }

    #[Test]
    public function it_can_enable_whatsapp_notifications(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableWhatsappNotifications', true)
            ->assertSet('enableWhatsappNotifications', true);
    }

    #[Test]
    public function it_clears_whatsapp_number_when_disabling_whatsapp_notifications(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableWhatsappNotifications', true)
            ->set('notificationWhatsappNumber', '+33612345678')
            ->set('enableWhatsappNotifications', false)
            ->assertSet('enableWhatsappNotifications', false)
            ->assertSet('notificationWhatsappNumber', null);
    }

    #[Test]
    public function it_validates_email_when_email_notifications_enabled(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['notificationEmail' => 'email']);
    }

    #[Test]
    public function it_requires_email_when_email_notifications_enabled(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->call('save')
            ->assertHasErrors(['notificationEmail' => 'required']);
    }

    #[Test]
    public function it_requires_whatsapp_number_when_whatsapp_notifications_enabled(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableWhatsappNotifications', true)
            ->call('save')
            ->assertHasErrors(['notificationWhatsappNumber' => 'required']);
    }

    #[Test]
    public function it_can_save_email_notifications_settings(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'admin@example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notification-updated');

        $this->assertDatabaseHas('whatsapp_account_settings', [
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@example.com',
            'enable_whatsapp_notifications' => false,
            'notification_whatsapp_number' => null,
        ]);
    }

    #[Test]
    public function it_can_save_whatsapp_notifications_settings(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableWhatsappNotifications', true)
            ->set('notificationWhatsappNumber', '+33612345678')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notification-updated');

        $this->assertDatabaseHas('whatsapp_account_settings', [
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => false,
            'notification_email' => null,
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);
    }

    #[Test]
    public function it_can_save_both_notification_types(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'admin@example.com')
            ->set('enableWhatsappNotifications', true)
            ->set('notificationWhatsappNumber', '+33612345678')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notification-updated');

        $this->assertDatabaseHas('whatsapp_account_settings', [
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);
    }

    #[Test]
    public function it_can_update_existing_settings(): void
    {
        $settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'old@example.com',
            'enable_whatsapp_notifications' => false,
            'notification_whatsapp_number' => null,
        ]);

        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('notificationEmail', 'new@example.com')
            ->set('enableWhatsappNotifications', true)
            ->set('notificationWhatsappNumber', '+33612345678')
            ->call('save')
            ->assertHasNoErrors();

        $settings->refresh();
        $this->assertEquals('new@example.com', $settings->notification_email);
        $this->assertTrue($settings->enable_whatsapp_notifications);
        $this->assertEquals('+33612345678', $settings->notification_whatsapp_number);
    }

    #[Test]
    public function it_handles_invalid_whatsapp_number_format(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableWhatsappNotifications', true)
            ->set('notificationWhatsappNumber', 'invalid-number')
            ->call('save')
            ->assertHasErrors(['notificationWhatsappNumber']);
    }

    #[Test]
    public function it_dispatches_notification_updated_event_after_save(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'admin@example.com')
            ->call('save')
            ->assertDispatched('notification-updated');
    }

    #[Test]
    public function it_validates_only_when_field_values_change(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'valid@example.com')
            ->assertHasNoErrors()
            ->set('notificationEmail', 'invalid-email')
            ->assertHasErrors(['notificationEmail' => 'email']);
    }

    #[Test]
    public function it_can_mount_component_with_any_account(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Livewire::test(NotificationConfig::class, ['account' => $otherAccount])
            ->assertSet('account.id', $otherAccount->id);
    }

    #[Test]
    public function it_can_disable_all_notifications(): void
    {
        $settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);

        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', false)
            ->set('enableWhatsappNotifications', false)
            ->call('save')
            ->assertHasNoErrors();

        $settings->refresh();
        $this->assertFalse($settings->enable_email_notifications);
        $this->assertFalse($settings->enable_whatsapp_notifications);
        $this->assertNull($settings->notification_email);
        $this->assertNull($settings->notification_whatsapp_number);
    }

    #[Test]
    public function it_does_not_interfere_with_field_visibility_when_toggling_checkboxes(): void
    {
        Livewire::test(NotificationConfig::class, ['account' => $this->account])
            ->set('enableEmailNotifications', true)
            ->set('notificationEmail', 'admin@example.com')
            ->set('enableWhatsappNotifications', true)
            ->assertSet('enableEmailNotifications', true)
            ->assertSet('notificationEmail', 'admin@example.com')
            ->assertSet('enableWhatsappNotifications', true);
    }
}
