<?php

namespace App\Http\Requests\PushSubscription;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Assuming any authenticated user can destroy their own subscription
    }

    public function rules(): array
    {
        return [
            'endpoint' => 'required|url',
        ];
    }
}
