<?php

/**
 * Script pour convertir les annotations @test en attributs #[Test]
 * Usage: php convert-test-annotations.php
 */
function convertTestFile(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Erreur: Impossible de lire $filePath\n";

        return;
    }

    $originalContent = $content;

    // Vérifier s'il y a déjà une import pour PHPUnit\Framework\Attributes\Test
    $hasTestImport = str_contains($content, 'use PHPUnit\Framework\Attributes\Test;');

    // Ajouter l'import si nécessaire et s'il y a des @test
    if (! $hasTestImport && str_contains($content, '/** @test */')) {
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
    } else {
        echo "⏭️  Aucun changement nécessaire: $filePath\n";
    }
}

function findTestFiles(string $directory): array
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $testFiles = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getFilename(), 'Test')) {
            $testFiles[] = $file->getPathname();
        }
    }

    return $testFiles;
}

// Script principal
$testsDirectory = __DIR__.'/tests';

if (! is_dir($testsDirectory)) {
    echo "Erreur: Le répertoire tests/ n'existe pas.\n";
    exit(1);
}

echo "🔍 Recherche des fichiers de test...\n";
$testFiles = findTestFiles($testsDirectory);

echo '📁 Trouvé '.count($testFiles)." fichiers de test\n\n";

foreach ($testFiles as $testFile) {
    convertTestFile($testFile);
}

echo "\n✨ Conversion terminée!\n";
echo "💡 Vous pouvez maintenant exécuter: php artisan test\n";
