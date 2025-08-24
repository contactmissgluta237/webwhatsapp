<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;

class MediaPreview extends Component
{
    public function __construct(
        public mixed $file,
        public int $index,
        public string $wireMethod = 'removeMediaFile',
        public bool $showDelete = true,
        public string $size = 'md' // sm, md, lg
    ) {}

    public function render()
    {
        return view('components.media-preview');
    }

    public function isImageFile(): bool
    {
        $mimeType = $this->getMimeType();

        return $mimeType && str_starts_with($mimeType, 'image/');
    }

    public function isVideoFile(): bool
    {
        $mimeType = $this->getMimeType();

        return $mimeType && str_starts_with($mimeType, 'video/');
    }

    public function isPdfFile(): bool
    {
        $mimeType = $this->getMimeType();

        return $mimeType === 'application/pdf';
    }

    public function isDocumentFile(): bool
    {
        $mimeType = $this->getMimeType();

        return $mimeType && in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function getFileName(): string
    {
        if (is_array($this->file) && isset($this->file['file_name'])) {
            return $this->file['file_name'];
        }

        return $this->file->getClientOriginalName();
    }

    public function getFileExtension(): string
    {
        if (is_array($this->file) && isset($this->file['extension'])) {
            return strtoupper($this->file['extension']);
        }

        return strtoupper($this->file->getClientOriginalExtension());
    }

    public function getFileSize(): string
    {
        $bytes = $this->getSizeInBytes();
        if (! $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $pow), 1).' '.$units[$pow];
    }

    public function getPreviewUrl(): ?string
    {
        if (! $this->isImageFile()) {
            return null;
        }

        if (is_array($this->file) && isset($this->file['url'])) {
            return $this->file['url'];
        }

        return $this->file->temporaryUrl();
    }

    private function getMimeType(): ?string
    {
        if (is_array($this->file) && isset($this->file['mime_type'])) {
            return $this->file['mime_type'];
        }

        return $this->file->getMimeType();
    }

    private function getSizeInBytes(): int
    {
        if (is_array($this->file) && isset($this->file['size'])) {
            return (int) $this->file['size'];
        }

        return $this->file->getSize();
    }

    public function getCardSize(): array
    {
        return match ($this->size) {
            'sm' => ['width' => '100px', 'height' => '120px'],
            'lg' => ['width' => '150px', 'height' => '180px'],
            default => ['width' => '120px', 'height' => '150px'], // md
        };
    }
}
