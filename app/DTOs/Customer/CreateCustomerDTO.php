<?php

namespace App\DTOs\Customer;

use App\DTOs\BaseDTO;
use Illuminate\Support\Facades\Hash;

class CreateCustomerDTO extends BaseDTO
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $password,
        public ?string $phone_number = null,
        public ?int $country_id = null,
        public ?string $referral_code = null,
        public bool $terms = false,
    ) {}

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'password' => Hash::make($this->password),
        ]);
    }
}
