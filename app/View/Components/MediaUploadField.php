<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;

class MediaUploadField extends Component
{
    public function __construct(
        public string $wireModel = 'mediaFiles',
        public bool $multiple = true,
        public int $maxFiles = 5,
        public string $accept = 'image/*,.pdf,.doc,.docx',
        public string $label = 'Médias',
        public ?string $helpText = null,
        public string $errorBag = 'mediaFiles.*'
    ) {
        $this->helpText = $this->helpText ?? "Maximum {$this->maxFiles} fichiers, 10MB par fichier.";
    }

    public function render()
    {
        return view('components.media-upload-field');
    }
}
