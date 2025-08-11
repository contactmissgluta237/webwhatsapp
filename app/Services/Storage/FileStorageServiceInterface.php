<?php

declare(strict_types=1);

namespace App\Services\Storage;

interface FileStorageServiceInterface
{
    public function save(string $directory, string $filename, string $content): string;
}
