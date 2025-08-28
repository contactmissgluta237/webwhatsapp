<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\WhatsApp;

use App\Constants\ValidationLimits;
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
            'agent_name' => 'required|string|max:'.ValidationLimits::AGENT_NAME_MAX_LENGTH,
            'agent_enabled' => 'boolean',
            'ai_model_id' => $agentEnabled ? 'required|exists:ai_models,id' : 'nullable|exists:ai_models,id',
            'agent_prompt' => $agentEnabled ? ['required', 'string', new PromptLengthRule] : ['nullable', 'string', new PromptLengthRule],
            'trigger_words' => 'nullable|string|max:'.ValidationLimits::TRIGGER_WORDS_MAX_LENGTH,
            'contextual_information' => 'nullable|string|max:'.ValidationLimits::CONTEXTUAL_INFO_MAX_LENGTH,
            'ignore_words' => 'nullable|string|max:'.ValidationLimits::IGNORE_WORDS_MAX_LENGTH,
            'response_time' => 'required|string|in:instant,fast,random,slow',
            'stop_on_human_reply' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'agent_name.required' => 'Le nom de l\'agent est obligatoire.',
            'agent_name.max' => 'Le nom de l\'agent ne peut pas dépasser '.ValidationLimits::AGENT_NAME_MAX_LENGTH.' caractères.',
            'ai_model_id.required' => 'Le modèle IA est obligatoire quand l\'agent est activé.',
            'ai_model_id.exists' => 'Le modèle IA sélectionné n\'existe pas.',
            'agent_prompt.required' => 'Le prompt de l\'agent est obligatoire quand l\'agent est activé.',
            'trigger_words.max' => 'Les mots déclencheurs ne peuvent pas dépasser '.ValidationLimits::TRIGGER_WORDS_MAX_LENGTH.' caractères.',
            'contextual_information.max' => 'Les informations contextuelles ne peuvent pas dépasser '.ValidationLimits::CONTEXTUAL_INFO_MAX_LENGTH.' caractères.',
            'ignore_words.max' => 'Les mots à ignorer ne peuvent pas dépasser '.ValidationLimits::IGNORE_WORDS_MAX_LENGTH.' caractères.',
            'response_time.required' => 'Le temps de réponse est obligatoire.',
            'response_time.in' => 'Le temps de réponse doit être : instant, fast, random ou slow.',
        ];
    }
}
