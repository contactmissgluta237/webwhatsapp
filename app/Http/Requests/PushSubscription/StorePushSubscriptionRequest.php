<?php

namespace App\Http\Requests\PushSubscription;

use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Assuming any authenticated user can store a subscription
    }

    public function rules(): array
    {
        return [
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ];
    }
}
