<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'utilisateur peut modifier ses propres paramètres de notification
        return true;
    }

    public function rules(): array
    {
        return [
            'whatsapp_account_id' => ['required', 'integer', 'exists:whatsapp_accounts,id'],
            'enable_email_notifications' => ['boolean'],
            'notification_email' => [
                $this->boolean('enable_email_notifications') ? 'required' : 'nullable',
                'email',
                'max:255',
            ],
            'enable_whatsapp_notifications' => ['boolean'],
            'notification_whatsapp_number' => [
                $this->boolean('enable_whatsapp_notifications') ? 'required' : 'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_account_id.required' => 'L\'ID du compte WhatsApp est obligatoire.',
            'whatsapp_account_id.exists' => 'Le compte WhatsApp spécifié n\'existe pas.',
            'notification_email.required' => 'L\'adresse email est obligatoire lorsque les notifications email sont activées.',
            'notification_email.email' => 'Veuillez entrer une adresse email valide.',
            'notification_email.max' => 'L\'adresse email est trop longue.',
            'notification_whatsapp_number.required' => 'Le numéro WhatsApp est obligatoire lorsque les notifications WhatsApp sont activées.',
            'notification_whatsapp_number.max' => 'Le numéro WhatsApp est trop long.',
        ];
    }
}
