<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\File;

class FileStorageService implements FileStorageServiceInterface
{
    public function save(string $directory, string $filename, string $content): string
    {
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = $directory.'/'.$filename;
        File::put($path, $content);

        return $path;
    }
}
