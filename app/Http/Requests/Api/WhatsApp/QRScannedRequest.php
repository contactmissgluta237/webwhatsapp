<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class QRScannedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming the webhook endpoint is public but secured by other means (e.g., network rules).
        // If auth is needed (e.g., API key), it would be implemented here.
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
            'sessionId' => 'required|string',
            'userId' => 'required|integer|exists:users,id',
            'whatsappData' => 'required|array',
            'whatsappData.me' => 'required|array',
            'whatsappData.me.user' => 'required|string',
        ];
    }
}
