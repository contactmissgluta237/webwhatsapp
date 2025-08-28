<?php

/**
 * Script pour identifier les fichiers de test tronqués
 * Usage: php find-truncated-files.php
 */
function isFileTruncated(string $filePath): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    // Vérifier si le fichier se termine abruptement sans }
    $content = trim($content);

    // Si le fichier ne se termine pas par } ou ne contient pas de class complète
    if (! str_ends_with($content, '}')) {
        return true;
    }

    // Vérifier si la déclaration de classe est incomplète (manque extends TestCase ou {)
    if (preg_match('/class\s+\w+\s*$/', $content)) {
        return true;
    }

    // Vérifier s'il y a une ligne "No newline at end of file" dans le contenu
    if (str_contains($content, 'No newline at end of file')) {
        return true;
    }

    return false;
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

echo "🔍 Recherche des fichiers de test tronqués...\n\n";
$testFiles = findTestFiles($testsDirectory);

$truncatedFiles = [];
foreach ($testFiles as $testFile) {
    if (isFileTruncated($testFile)) {
        $truncatedFiles[] = $testFile;
    }
}

if (empty($truncatedFiles)) {
    echo "✅ Aucun fichier tronqué trouvé!\n";
} else {
    echo '❌ Fichiers tronqués trouvés ('.count($truncatedFiles)."):\n\n";
    foreach ($truncatedFiles as $file) {
        echo "- $file\n";
    }
}

echo "\n🔚 Terminé.\n";
