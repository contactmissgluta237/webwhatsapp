<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp\Webhook;

use Illuminate\Foundation\Http\FormRequest;

final class SessionConnectedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'session_id' => 'required|string',
            'phone_number' => 'required|string',
            'whatsapp_data' => 'sometimes|array',
        ];
    }
}
