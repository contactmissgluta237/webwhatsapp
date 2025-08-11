<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class PushNotificationDTO
{
    public function __construct(
        public string $title,
        public string $body,
        public string $icon,
        public string $badge,
        public string $tag,
        public array $data,
        public array $actions,
        public bool $requireInteraction = true,
        public bool $silent = false,
        public array $vibrate = [200, 100, 200, 100, 200]
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'tag' => $this->tag,
            'data' => $this->data,
            'actions' => $this->actions,
            'requireInteraction' => $this->requireInteraction,
            'silent' => $this->silent,
            'vibrate' => $this->vibrate,
        ];
    }
}
