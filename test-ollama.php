#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Test de connexion Ollama...\n\n";

$ollamaUrl = 'http://209.126.83.125:11434';

// Test 1: Version
echo "1. Test version API...\n";
try {
    $response = Http::timeout(10)->connectTimeout(5)->get($ollamaUrl.'/api/version');
    if ($response->successful()) {
        $data = $response->json();
        echo '✅ Version: '.($data['version'] ?? 'inconnue')."\n";
    } else {
        echo '❌ Erreur: '.$response->status()."\n";
    }
} catch (Exception $e) {
    echo '❌ Exception: '.$e->getMessage()."\n";
}

echo "\n";

// Test 2: Modèles disponibles
echo "2. Test modèles disponibles...\n";
try {
    $response = Http::timeout(10)->connectTimeout(5)->get($ollamaUrl.'/api/tags');
    if ($response->successful()) {
        $data = $response->json();
        $models = $data['models'] ?? [];
        echo '✅ Modèles trouvés: '.count($models)."\n";
        foreach ($models as $model) {
            echo '  - '.$model['name']."\n";
        }
    } else {
        echo '❌ Erreur: '.$response->status()."\n";
    }
} catch (Exception $e) {
    echo '❌ Exception: '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: Chat avec un modèle
echo "3. Test chat API...\n";
try {
    $payload = [
        'model' => 'gemma2:2b',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello! Just say OK to confirm you work.',
            ],
        ],
        'stream' => false,
    ];

    $response = Http::timeout(60)
        ->connectTimeout(10)
        ->post($ollamaUrl.'/api/chat', $payload);

    if ($response->successful()) {
        $data = $response->json();
        $content = $data['message']['content'] ?? 'Pas de contenu';
        echo '✅ Réponse: '.trim($content)."\n";
        echo '✅ Durée totale: '.($data['total_duration'] ?? 0) / 1000000 ."ms\n";
    } else {
        echo '❌ Erreur: '.$response->status().' - '.$response->body()."\n";
    }
} catch (Exception $e) {
    echo '❌ Exception: '.$e->getMessage()."\n";
}

echo "\n🎯 Test terminé.\n";
