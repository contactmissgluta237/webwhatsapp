<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$userId,
            'phone_number' => 'nullable|string|unique:users,phone_number,'.$userId,
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('validation.profile.first_name.required'),
            'first_name.string' => __('validation.profile.first_name.string'),
            'first_name.max' => __('validation.profile.first_name.max'),
            'last_name.required' => __('validation.profile.last_name.required'),
            'last_name.string' => __('validation.profile.last_name.string'),
            'last_name.max' => __('validation.profile.last_name.max'),
            'email.required' => __('validation.profile.email.required'),
            'email.email' => __('validation.profile.email.email'),
            'email.unique' => __('validation.profile.email.unique'),
            'phone_number.string' => __('validation.profile.phone_number.string'),
            'phone_number.unique' => __('validation.profile.phone_number.unique'),
        ];
    }
}
