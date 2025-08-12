<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class AiConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic for this request
        // For now, we'll assume true, but this should be properly implemented
        // based on your application's authorization rules.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agent_name' => 'required|string|max:100',
            'agent_enabled' => 'boolean',
            'ai_model_id' => 'nullable|exists:ai_models,id',
            'agent_prompt' => 'nullable|string|max:2000',
            'trigger_words' => 'nullable|string|max:500',
            'response_time' => 'required|string|in:instant,fast,random,slow',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agent_name.required' => __('Le nom de l\'agent est obligatoire.'),
            'agent_name.max' => __('Le nom de l\'agent ne peut pas dépasser 100 caractères.'),
            'ai_model_id.exists' => __('Le modèle sélectionné est invalide.'),
            'agent_prompt.max' => __('Le prompt ne peut pas dépasser 2000 caractères.'),
            'trigger_words.max' => __('Les mots déclencheurs ne peuvent pas dépasser 500 caractères.'),
            'response_time.in' => __('Le délai de réponse sélectionné est invalide.'),
        ];
    }
}
