<?php

namespace App\Services\Customer;

use App\DTOs\Customer\CreateCustomerDTO;
use App\Enums\UserRole;
use App\Events\CustomerCreatedEvent;
use App\Models\Customer;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use App\Services\BaseService;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;

class CustomerService extends BaseService
{
    public function __construct(
        private AccountActivationServiceInterface $activationService,
        private CurrencyService $currencyService
    ) {}

    public function create(CreateCustomerDTO $dto): Customer
    {
        return DB::transaction(function () use ($dto) {
            $userData = $dto->toArray();
            $referralCode = $userData['referral_code'] ?? null;
            unset($userData['referral_code'], $userData['terms']);

            $user = User::create($userData);
            $user->assignRole(UserRole::CUSTOMER()->value);

            $this->currencyService->setCurrencyForNewUser($user, $userData['country_id'] ?? null);

            $referrerId = null;
            if ($referralCode) {
                $referrerUser = User::findByAffiliationCode($referralCode);
                if ($referrerUser && $referrerUser->customer) {
                    $referrerId = $referrerUser->customer->id;
                }
            }

            $customer = Customer::create([
                'user_id' => $user->id,
                'referrer_id' => $referrerId,
            ]);

            $this->activationService->sendActivationCode($user->email);

            $customer->load(['user']);
            event(new CustomerCreatedEvent($customer));

            return $customer;
        });
    }

    protected function getMediaFields(): array
    {
        return [];
    }

    protected function getModel(): string
    {
        return Customer::class;
    }

    protected function getMediaCollectionName(): string
    {
        return 'images';
    }
}
