<?php

namespace Tests\Unit\Rules;

use App\Rules\PromptLengthRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptLengthRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_prompt_within_default_limit(): void
    {
        $rule = new PromptLengthRule;
        $prompt = str_repeat('A', 5000); // 5000 chars
        $failed = false;

        $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Prompt within default limit should pass validation');
    }

    public function test_validates_prompt_exceeding_default_limit(): void
    {
        $rule = new PromptLengthRule;
        $prompt = str_repeat('A', 15000); // 15000 chars > 10000 default
        $failed = false;
        $failMessage = '';

        $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed, 'Prompt exceeding default limit should fail validation');
        $this->assertStringContainsString('10', $failMessage, 'Error message should mention the limit');
        $this->assertStringContainsString('15000', $failMessage, 'Error message should mention current length');
    }

    public function test_validates_prompt_within_custom_limit(): void
    {
        $customLimit = 3000;
        $rule = new PromptLengthRule($customLimit);
        $prompt = str_repeat('A', 2500); // 2500 chars < 3000
        $failed = false;

        $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Prompt within custom limit should pass validation');
    }

    public function test_validates_prompt_exceeding_custom_limit(): void
    {
        $customLimit = 5000;
        $rule = new PromptLengthRule($customLimit);
        $prompt = str_repeat('A', 6000); // 6000 chars > 5000
        $failed = false;
        $failMessage = '';

        $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed, 'Prompt exceeding custom limit should fail validation');
        $this->assertStringContainsString('5', $failMessage, 'Error message should mention custom limit');
        $this->assertStringContainsString('6000', $failMessage, 'Error message should mention current length');
    }

    public function test_validates_null_prompt(): void
    {
        $rule = new PromptLengthRule(1000);
        $failed = false;

        $rule->validate('agent_prompt', null, function (string $message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Null prompt should pass validation');
    }

    public function test_validates_empty_prompt(): void
    {
        $rule = new PromptLengthRule(1000);
        $failed = false;

        $rule->validate('agent_prompt', '', function (string $message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Empty prompt should pass validation');
    }

    public function test_validates_prompt_at_exact_limit(): void
    {
        $customLimit = 1000;
        $rule = new PromptLengthRule($customLimit);
        $prompt = str_repeat('A', $customLimit); // Exactly at limit
        $failed = false;

        $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Prompt at exact limit should pass validation');
    }

    public function test_validates_prompt_one_char_over_limit(): void
    {
        $customLimit = 1000;
        $rule = new PromptLengthRule($customLimit);
        $prompt = str_repeat('A', $customLimit + 1); // One char over limit
        $failed = false;

        $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Prompt one character over limit should fail validation');
    }

    public function test_different_custom_limits(): void
    {
        $testCases = [
            ['limit' => 3000, 'promptSize' => 2999, 'shouldPass' => true],
            ['limit' => 3000, 'promptSize' => 3000, 'shouldPass' => true],
            ['limit' => 3000, 'promptSize' => 3001, 'shouldPass' => false],
            ['limit' => 7000, 'promptSize' => 6999, 'shouldPass' => true],
            ['limit' => 7000, 'promptSize' => 7001, 'shouldPass' => false],
            ['limit' => 10000, 'promptSize' => 9999, 'shouldPass' => true],
            ['limit' => 10000, 'promptSize' => 10001, 'shouldPass' => false],
        ];

        foreach ($testCases as $case) {
            $rule = new PromptLengthRule($case['limit']);
            $prompt = str_repeat('A', $case['promptSize']);
            $failed = false;

            $rule->validate('agent_prompt', $prompt, function (string $message) use (&$failed) {
                $failed = true;
            });

            $expectation = $case['shouldPass'] ? 'pass' : 'fail';
            $this->assertEquals($case['shouldPass'], ! $failed,
                "Prompt of {$case['promptSize']} chars with limit {$case['limit']} should {$expectation}");
        }
    }
}
