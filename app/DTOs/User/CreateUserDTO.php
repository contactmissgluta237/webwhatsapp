<?php

namespace App\DTOs\User;

use App\DTOs\BaseDTO;
use App\Enums\UserRole;
use Illuminate\Http\UploadedFile;

class CreateUserDTO extends BaseDTO
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $phone_number,
        public string $password,
        public ?string $address,
        public bool $is_active,
        public UserRole $role,
        /** @var array<int> $distribution_center_ids */
        public ?array $distribution_center_ids = [],
        public readonly ?UploadedFile $image = null,
    ) {}

    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'image' => $this->image ? $this->image->getClientOriginalName() : null,
            ]
        );
    }
}
