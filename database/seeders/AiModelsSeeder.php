<?php

namespace Database\Seeders;

use App\Services\AI\AiConfigurationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = AiConfigurationService::getDefaultModels();

        DB::table('ai_models')->insert($models);
    }
}
