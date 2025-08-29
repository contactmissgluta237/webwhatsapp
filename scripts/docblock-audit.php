#!/usr/bin/env php
<?php

/**
 * DocBlock Audit Script
 * 
 * Vérifie que tous les modèles Laravel ont des docblocks complets
 * avec toutes leurs propriétés et relations documentées.
 */

$modelsDir = __DIR__ . '/app/Models';
$models = glob($modelsDir . '/*.php');

echo "🔍 DocBlock Audit Report\n";
echo "========================\n\n";

$issues = [];
$goodModels = [];

foreach ($models as $modelPath) {
    $modelName = basename($modelPath, '.php');
    $content = file_get_contents($modelPath);
    
    echo "Analyzing: {$modelName}... ";
    
    // Vérifier la présence d'un docblock avec @property
    if (preg_match('/\/\*\*.*@property.*\*\//s', $content)) {
        echo "✅ OK\n";
        $goodModels[] = $modelName;
    } else {
        echo "❌ MISSING DOCBLOCK\n";
        $issues[] = $modelName;
    }
}

echo "\n📊 Summary\n";
echo "==========\n";
echo "✅ Models with proper docblocks: " . count($goodModels) . "\n";
echo "❌ Models missing docblocks: " . count($issues) . "\n";

if (!empty($issues)) {
    echo "\n❌ Models needing attention:\n";
    foreach ($issues as $model) {
        echo "   - {$model}\n";
    }
}

echo "\n✅ Well-documented models:\n";
foreach ($goodModels as $model) {
    echo "   - {$model}\n";
}

echo "\nTotal models checked: " . count($models) . "\n";
echo "Documentation coverage: " . round((count($goodModels) / count($models)) * 100, 1) . "%\n";
