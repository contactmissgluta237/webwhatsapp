<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Customer\WhatsApp;

use App\Constants\ValidationLimits;
use App\Http\Requests\Customer\WhatsApp\AiConfigurationRequest;
use App\Models\AiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class AiConfigurationRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return AiConfigurationRequest::class;
    }

    protected function getValidData(): array
    {
        $aiModel = AiModel::factory()->create();

        return [
            'agent_name' => 'Assistant IA',
            'agent_enabled' => true,
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Vous êtes un assistant IA utile et bienveillant.',
            'trigger_words' => 'aide, support, question',
            'contextual_information' => 'Informations sur le contexte du bot.',
            'ignore_words' => 'spam, publicité',
            'response_time' => 'random',
            'stop_on_human_reply' => false,
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'agent_name required' => [
                'agent_name' => '',
                'expected_error_field' => 'agent_name',
            ],
            'agent_name too long' => [
                'agent_name' => str_repeat('a', ValidationLimits::AGENT_NAME_MAX_LENGTH + 1),
                'expected_error_field' => 'agent_name',
            ],
            'trigger_words too long' => [
                'trigger_words' => str_repeat('a', ValidationLimits::TRIGGER_WORDS_MAX_LENGTH + 1),
                'expected_error_field' => 'trigger_words',
            ],
            'contextual_information too long' => [
                'contextual_information' => str_repeat('a', ValidationLimits::CONTEXTUAL_INFO_MAX_LENGTH + 1),
                'expected_error_field' => 'contextual_information',
            ],
            'ignore_words too long' => [
                'ignore_words' => str_repeat('a', ValidationLimits::IGNORE_WORDS_MAX_LENGTH + 1),
                'expected_error_field' => 'ignore_words',
            ],
            'response_time invalid value' => [
                'response_time' => 'invalid',
                'expected_error_field' => 'response_time',
            ],
            'response_time required' => [
                'response_time' => '',
                'expected_error_field' => 'response_time',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'minimal valid data with agent disabled' => [
                'agent_name' => 'Bot Simple',
                'agent_enabled' => false,
                'response_time' => 'instant',
            ],
            'maximum length fields' => [
                'agent_name' => str_repeat('a', ValidationLimits::AGENT_NAME_MAX_LENGTH),
                'agent_enabled' => false,
                'trigger_words' => str_repeat('a', ValidationLimits::TRIGGER_WORDS_MAX_LENGTH),
                'contextual_information' => str_repeat('a', ValidationLimits::CONTEXTUAL_INFO_MAX_LENGTH),
                'ignore_words' => str_repeat('a', ValidationLimits::IGNORE_WORDS_MAX_LENGTH),
                'response_time' => 'slow',
                'stop_on_human_reply' => false,
            ],
            'nullable fields when agent disabled' => [
                'agent_name' => 'Agent Désactivé',
                'agent_enabled' => false,
                'ai_model_id' => null,
                'agent_prompt' => null,
                'trigger_words' => null,
                'contextual_information' => null,
                'ignore_words' => null,
                'response_time' => 'fast',
                'stop_on_human_reply' => false,
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'agent_name.required',
            'agent_name.max',
            'trigger_words.max',
            'contextual_information.max',
            'ignore_words.max',
            'response_time.required',
            'response_time.in',
        ];
    }
}
