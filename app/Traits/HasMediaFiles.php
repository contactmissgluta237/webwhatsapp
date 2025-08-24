<?php

declare(strict_types=1);

namespace App\Traits;

trait HasMediaFiles
{
    // 🔥 HELPERS pour les fichiers
    public function isImageFile($file): bool
    {
        return $file && str_starts_with($file->getMimeType(), 'image/');
    }

    public function isVideoFile($file): bool
    {
        return $file && str_starts_with($file->getMimeType(), 'video/');
    }

    public function isPdfFile($file): bool
    {
        return $file && $file->getMimeType() === 'application/pdf';
    }

    public function isDocumentFile($file): bool
    {
        return $file && in_array($file->getMimeType(), [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $pow), 1).' '.$units[$pow];
    }

    public function getFileExtension($file): string
    {
        return strtoupper($file->getClientOriginalExtension());
    }
}
