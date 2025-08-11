<?php

namespace App\DTOs\User;

use App\DTOs\BaseDTO;
use App\Enums\UserRole;
use Illuminate\Http\UploadedFile;

class UpdateUserDTO extends BaseDTO
{
    public function __construct(
        public ?int $id = null,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $email = null,
        public ?string $phone_number = null,
        public ?string $password = null,
        public ?bool $is_active = null,
        public ?UserRole $role = null,
        /** @var array<int> $distribution_center_ids */
        public ?array $distribution_center_ids = [],
        public ?UploadedFile $image = null,
        public ?string $address = null,
    ) {}
}
