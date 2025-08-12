<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WhatsAppAccountStopOnHumanReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_stop_on_human_reply_field_exists_and_defaults_to_false(): void
    {
        $user = User::factory()->create();
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($account->stop_on_human_reply);
        $this->assertIsBool($account->stop_on_human_reply);
    }

    public function test_can_update_stop_on_human_reply(): void
    {
        $user = User::factory()->create();
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        $account->update(['stop_on_human_reply' => true]);
        $account->refresh();

        $this->assertTrue($account->stop_on_human_reply);
    }

    public function test_stop_on_human_reply_is_fillable(): void
    {
        $user = User::factory()->create();
        
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'stop_on_human_reply' => true,
        ]);

        $this->assertTrue($account->stop_on_human_reply);
    }

    public function test_stop_on_human_reply_is_cast_to_boolean(): void
    {
        $user = User::factory()->create();
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        // Test avec valeur string "1"
        $account->stop_on_human_reply = "1";
        $account->save();
        $account->refresh();

        $this->assertIsBool($account->stop_on_human_reply);
        $this->assertTrue($account->stop_on_human_reply);

        // Test avec valeur string "0"
        $account->stop_on_human_reply = "0";
        $account->save();
        $account->refresh();

        $this->assertIsBool($account->stop_on_human_reply);
        $this->assertFalse($account->stop_on_human_reply);
    }

    public function test_mass_assignment_includes_stop_on_human_reply(): void
    {
        $user = User::factory()->create();
        $aiModel = AiModel::factory()->create([
            'provider' => 'ollama',
            'is_active' => true,
        ]);

        $data = [
            'user_id' => $user->id,
            'session_name' => 'Test Session',
            'session_id' => 'test-session-id',
            'agent_enabled' => true,
            'ai_model_id' => $aiModel->id,
            'response_time' => 'fast',
            'stop_on_human_reply' => true,
        ];

        $account = WhatsAppAccount::create($data);

        $this->assertTrue($account->stop_on_human_reply);
        $this->assertEquals('Test Session', $account->session_name);
    }
}
