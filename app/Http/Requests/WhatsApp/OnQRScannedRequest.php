<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class OnQRScannedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sessionId' => 'required|string',
            'userId' => 'required|integer',
            'whatsappData' => 'required|array',
            'whatsappData.phone' => 'sometimes|string',
        ];
    }
}
