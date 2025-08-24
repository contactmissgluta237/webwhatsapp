<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::CODING_STYLE,
    ])
    ->withTypeCoverageLevel(0)
    ->withSkip([
        // Ignorer certains fichiers si nécessaire
        __DIR__.'/app/Models/User.php',

        // IMPORTANT: Ne PAS toucher aux Enums Spatie
        __DIR__.'/app/Enums/',

        // Exclure les règles spécifiques qui touchent aux enums Spatie
        \Rector\Php81\Rector\MethodCall\SpatieEnumMethodCallToEnumConstRector::class,

        // Optionnel: Exclure d'autres règles si nécessaire
        ReadOnlyPropertyRector::class => [
            __DIR__.'/app/Models/',
        ],
    ]);
