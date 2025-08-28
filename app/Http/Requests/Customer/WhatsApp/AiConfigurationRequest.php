<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\WhatsApp;

use App\Rules\PromptLengthRule;
use Illuminate\Foundation\Http\FormRequest;

final class AiConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agentEnabled = $this->boolean('agent_enabled');

        return [
            'agent_name' => 'required|string|max:100',
            'agent_enabled' => 'boolean',
            'ai_model_id' => $agentEnabled ? 'required|exists:ai_models,id' : 'nullable|exists:ai_models,id',
            'agent_prompt' => $agentEnabled ? ['required', 'string', new PromptLengthRule] : ['nullable', 'string', new PromptLengthRule],
            'trigger_words' => 'nullable|string|max:500',
            'contextual_information' => 'nullable|string|max:5000',
            'ignore_words' => 'nullable|string|max:500',
            'response_time' => 'required|string|in:instant,fast,random,slow',
            'stop_on_human_reply' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'agent_name.required' => 'Le nom de l\'agent est obligatoire.',
            'agent_name.max' => 'Le nom de l\'agent ne peut pas dépasser 100 caractères.',
            'ai_model_id.required' => 'Le modèle IA est obligatoire quand l\'agent est activé.',
            'ai_model_id.exists' => 'Le modèle IA sélectionné n\'existe pas.',
            'agent_prompt.required' => 'Le prompt de l\'agent est obligatoire quand l\'agent est activé.',
            'trigger_words.max' => 'Les mots déclencheurs ne peuvent pas dépasser 500 caractères.',
            'contextual_information.max' => 'Les informations contextuelles ne peuvent pas dépasser 5000 caractères.',
            'ignore_words.max' => 'Les mots à ignorer ne peuvent pas dépasser 500 caractères.',
            'response_time.required' => 'Le temps de réponse est obligatoire.',
            'response_time.in' => 'Le temps de réponse doit être : instant, fast, random ou slow.',
        ];
    }
}
