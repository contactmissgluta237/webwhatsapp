<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class OnStatusUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sessionId' => 'required|string',
            'status' => 'required|string',
            'data' => 'sometimes|array',
        ];
    }
}
