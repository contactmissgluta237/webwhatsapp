<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class WhatsAppAccountMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function whatsapp_accounts_table_has_stop_on_human_reply_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('whatsapp_accounts', 'stop_on_human_reply'),
            'La table whatsapp_accounts doit avoir la colonne stop_on_human_reply'
        );
    }

    /** @test */
    public function stop_on_human_reply_column_has_correct_attributes(): void
    {
        $columns = Schema::getColumnListing('whatsapp_accounts');
        $this->assertContains('stop_on_human_reply', $columns);

        // Vérifier que la colonne peut stocker des booléens
        $columnType = Schema::getColumnType('whatsapp_accounts', 'stop_on_human_reply');
        $this->assertContains($columnType, ['boolean', 'tinyint'],
            'La colonne stop_on_human_reply doit être de type boolean ou tinyint');
    }

    /** @test */
    public function stop_on_human_reply_has_default_false_value(): void
    {
        // Créer un compte WhatsApp sans spécifier stop_on_human_reply
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
        ]);

        // Vérifier que la valeur par défaut est false
        $this->assertFalse($account->fresh()->stop_on_human_reply);
    }

    /** @test */
    public function agent_enabled_has_default_false_value(): void
    {
        // Créer un compte WhatsApp sans spécifier agent_enabled
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
        ]);

        // Vérifier que la valeur par défaut est false
        $this->assertFalse($account->fresh()->agent_enabled, 'agent_enabled should default to false');
    }

    /** @test */
    public function can_set_stop_on_human_reply_to_true(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'stop_on_human_reply' => true,
        ]);

        $this->assertTrue($account->stop_on_human_reply);
    }

    /** @test */
    public function can_set_agent_enabled_to_true(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_enabled' => true,
        ]);

        $this->assertTrue($account->agent_enabled);
    }

    /** @test */
    public function can_update_stop_on_human_reply_value(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'stop_on_human_reply' => false,
        ]);

        // Mettre à jour la valeur
        $account->update(['stop_on_human_reply' => true]);

        $this->assertTrue($account->fresh()->stop_on_human_reply);

        // Remettre à false
        $account->update(['stop_on_human_reply' => false]);

        $this->assertFalse($account->fresh()->stop_on_human_reply);
    }

    /** @test */
    public function stop_on_human_reply_is_included_in_fillable_array(): void
    {
        $account = new \App\Models\WhatsAppAccount;

        $this->assertContains('stop_on_human_reply', $account->getFillable(),
            'stop_on_human_reply doit être dans le tableau fillable du modèle');
    }

    /** @test */
    public function stop_on_human_reply_is_cast_to_boolean(): void
    {
        $account = new \App\Models\WhatsAppAccount;
        $casts = $account->getCasts();

        $this->assertArrayHasKey('stop_on_human_reply', $casts,
            'stop_on_human_reply doit être défini dans les casts');

        $this->assertEquals('boolean', $casts['stop_on_human_reply'],
            'stop_on_human_reply doit être casté en boolean');
    }

    /** @test */
    public function all_required_whatsapp_account_columns_exist(): void
    {
        $requiredColumns = [
            'id',
            'user_id',
            'session_name',
            'session_id',
            'phone_number',
            'status',
            'qr_code',
            'last_seen_at',
            'session_data',
            'agent_name',
            'agent_enabled',
            'ai_model_id',
            'response_time',
            'agent_prompt',
            'trigger_words',
            'stop_on_human_reply', // Notre nouvelle colonne
            'contextual_information',
            'ignore_words',
            'last_ai_response_at',
            'daily_ai_responses',
            'created_at',
            'updated_at',
        ];

        $existingColumns = Schema::getColumnListing('whatsapp_accounts');

        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $existingColumns,
                "La colonne '{$column}' doit exister dans la table whatsapp_accounts");
        }
    }
}
