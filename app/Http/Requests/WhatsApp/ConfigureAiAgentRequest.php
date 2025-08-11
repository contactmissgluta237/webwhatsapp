<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureAiAgentRequest extends FormRequest
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
        return [
            'ai_model_id' => 'required_if:ai_agent_enabled,true|exists:ai_models,id',
            'ai_prompt' => 'nullable|string|max:1000',
            'ai_trigger_words' => 'nullable|string|max:500',
            'ai_response_time' => 'required|string',
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
            'ai_model_id.required_if' => 'Veuillez sélectionner un modèle AI.',
            'ai_model_id.exists' => 'Le modèle sélectionné n\'existe pas.',
            'ai_prompt.max' => 'Le prompt ne peut pas dépasser 1000 caractères.',
            'ai_trigger_words.max' => 'Les mots déclencheurs ne peuvent pas dépasser 500 caractères.',
        ];
    }
}
