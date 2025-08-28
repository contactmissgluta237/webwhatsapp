<?php

/**
 * Script pour convertir UN SEUL fichier de @test vers #[Test]
 * Usage: php convert-single-file.php path/to/file.php
 */
function convertTestFile(string $filePath): bool
{
    if (! file_exists($filePath)) {
        echo "❌ Fichier non trouvé: $filePath\n";

        return false;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "❌ Erreur: Impossible de lire $filePath\n";

        return false;
    }

    $originalContent = $content;

    // Vérifier s'il y a des @test
    if (! str_contains($content, '/** @test */')) {
        echo "ℹ️  Aucune annotation @test trouvée dans $filePath\n";

        return true;
    }

    // Vérifier s'il y a déjà une import pour PHPUnit\Framework\Attributes\Test
    $hasTestImport = str_contains($content, 'use PHPUnit\Framework\Attributes\Test;');

    // Ajouter l'import si nécessaire
    if (! $hasTestImport) {
        // Trouver la position après les autres imports
        if (preg_match('/^(.*?)(class\s+\w+)/ms', $content, $matches)) {
            $beforeClass = $matches[1];
            $classAndRest = $matches[2];

            // Ajouter l'import avant la déclaration de classe
            $lines = explode("\n", $beforeClass);
            $lastImportIndex = -1;

            // Trouver le dernier import
            foreach ($lines as $index => $line) {
                if (str_starts_with(trim($line), 'use ')) {
                    $lastImportIndex = $index;
                }
            }

            // Insérer le nouvel import après le dernier import existant
            if ($lastImportIndex !== -1) {
                array_splice($lines, $lastImportIndex + 1, 0, 'use PHPUnit\Framework\Attributes\Test;');
                $beforeClass = implode("\n", $lines);
                $content = $beforeClass.$classAndRest;
            }
        }
    }

    // Convertir les annotations @test en attributs #[Test]
    $content = preg_replace(
        '/(\s*)\/\*\*\s*@test\s*\*\/\s*\n(\s*)public function/',
        '$1#[Test]'."\n".'$2public function',
        $content
    );

    // Sauvegarder seulement si le contenu a changé
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Converti: $filePath\n";

        return true;
    } else {
        echo "⏭️  Aucun changement nécessaire: $filePath\n";

        return true;
    }
}

// Script principal
if ($argc !== 2) {
    echo "Usage: php convert-single-file.php path/to/TestFile.php\n";
    exit(1);
}

$filePath = $argv[1];
echo "🔧 Conversion du fichier: $filePath\n\n";

if (convertTestFile($filePath)) {
    echo "\n✨ Conversion terminée!\n";
} else {
    echo "\n❌ Erreur lors de la conversion!\n";
    exit(1);
}
