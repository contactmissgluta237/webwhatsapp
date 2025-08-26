<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp\Webhook;

use Illuminate\Foundation\Http\FormRequest;

final class IncomingMessageRequest extends FormRequest
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
            'event' => 'required|string',
            'session_id' => 'required|string',
            'session_name' => 'required|string',
            'message' => 'required|array',
            'message.id' => 'required|string',
            'message.from' => 'required|string',
            'message.body' => 'required|string',
            'message.timestamp' => 'required|integer',
            'message.type' => 'required|string',
            'message.isGroup' => 'required|boolean',
            // Optional contact information
            'message.contactName' => 'nullable|string|max:255',
            'message.pushName' => 'nullable|string|max:255',
            'message.publicName' => 'nullable|string|max:255',
            'message.displayName' => 'nullable|string|max:255',
        ];
    }
}
