<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

final class NotificationConfig extends Component
{
    use AuthorizesRequests;

    public WhatsAppAccount $account;

    public ?WhatsAppAccountSetting $settings = null;

    public bool $enableEmailNotifications = false;

    public ?string $notificationEmail = null;

    public bool $enableWhatsappNotifications = false;

    public ?string $notificationWhatsappNumber = null;

    protected function rules(): array
    {
        return [
            'enableEmailNotifications' => 'boolean',
            'notificationEmail' => [
                $this->enableEmailNotifications ? 'required' : 'nullable',
                'email',
                'max:255',
            ],
            'enableWhatsappNotifications' => 'boolean',
            'notificationWhatsappNumber' => [
                $this->enableWhatsappNotifications ? 'required' : 'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'notificationEmail.required' => 'Email address is required when email notifications are enabled.',
            'notificationEmail.email' => 'Please enter a valid email address.',
            'notificationEmail.max' => 'Email address is too long.',
            'notificationWhatsappNumber.required' => 'WhatsApp number is required when WhatsApp notifications are enabled.',
            'notificationWhatsappNumber.max' => 'WhatsApp number is too long.',
        ];
    }

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $account->settings;
        $this->settings = $settings;

        if ($this->settings) {
            $this->enableEmailNotifications = $this->settings->enable_email_notifications;
            $this->notificationEmail = $this->settings->notification_email;
            $this->enableWhatsappNotifications = $this->settings->enable_whatsapp_notifications;
            $this->notificationWhatsappNumber = $this->settings->notification_whatsapp_number;
        }
    }

    public function updatedEnableEmailNotifications(): void
    {
        if (! $this->enableEmailNotifications) {
            $this->notificationEmail = null;
            $this->resetErrorBag(['notificationEmail']);
        }
    }

    public function updatedNotificationEmail(): void
    {
        if ($this->enableEmailNotifications) {
            $this->validateOnly('notificationEmail');
        }
    }

    public function updatedEnableWhatsappNotifications(): void
    {
        if (! $this->enableWhatsappNotifications) {
            $this->notificationWhatsappNumber = null;
            $this->resetErrorBag(['notificationWhatsappNumber']);
        }
    }

    public function updatedNotificationWhatsappNumber(): void
    {
        if ($this->enableWhatsappNotifications) {
            $this->validateOnly('notificationWhatsappNumber');
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->account);

        $this->validate();

        try {
            $data = [
                'whatsapp_account_id' => $this->account->id,
                'enable_email_notifications' => $this->enableEmailNotifications,
                'notification_email' => $this->enableEmailNotifications ? $this->notificationEmail : null,
                'enable_whatsapp_notifications' => $this->enableWhatsappNotifications,
                'notification_whatsapp_number' => $this->enableWhatsappNotifications ? $this->notificationWhatsappNumber : null,
            ];

            if ($this->settings) {
                $this->settings->update($data);
            } else {
                $this->settings = WhatsAppAccountSetting::create($data);
                $this->account->refresh();
            }

            $this->dispatch('notification-updated');
            session()->flash('success', 'Configuration des notifications mise à jour avec succès.');

        } catch (\InvalidArgumentException $e) {
            $this->addError('notificationWhatsappNumber', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customer.whats-app.notification-config');
    }
}
