<?php

namespace App\Services\User;

use App\Enums\UserRole;
use App\Events\UserDeletedEvent;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Shared\Media\MediaServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(
        protected MediaServiceInterface $mediaService,
    ) {
        parent::__construct($mediaService);
    }

    public function createUser(
        string $first_name,
        string $last_name,
        string $email,
        string $phone_number,
        string $password,
        bool $is_active,
        array $roles,
        ?UploadedFile $image = null
    ): User {
        return DB::transaction(function () use (
            $first_name,
            $last_name,
            $email,
            $phone_number,
            $password,
            $is_active,
            $roles,
            $image
        ) {
            $user = User::create([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone_number' => $phone_number,
                'password' => Hash::make($password),
                'is_active' => $is_active,
            ]);

            $this->handleUserMedia($user, $image);
            $user->syncRoles($roles);

            return $user;
        });
    }

    public function updateUser(
        User $user,
        string $first_name,
        string $last_name,
        string $email,
        string $phone_number,
        ?string $password,
        bool $is_active,
        array $roles,
        string|UploadedFile|null $image = null
    ): User {
        return DB::transaction(function () use (
            $user,
            $first_name,
            $last_name,
            $email,
            $phone_number,
            $password,
            $is_active,
            $roles,
            $image
        ) {
            $updateData = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone_number' => $phone_number,
                'is_active' => $is_active,
            ];

            if ($password) {
                $updateData['password'] = Hash::make($password);
            }

            $user->update($updateData);

            $this->handleUserMedia($user, $image);
            $user->syncRoles($roles);

            return $user;
        });
    }

    /**
     * Delete a user from the system
     *
     * @param  User  $user  The user to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(Model $user): bool
    {
        return DB::transaction(function () use ($user) {
            $result = $user->delete();

            if ($result) {
                UserDeletedEvent::dispatch($user);
            }

            return $result;
        });
    }

    /**
     * Find a user by their ID
     *
     * @param  int  $id  The user ID to find
     * @return User|null The user if found, null otherwise
     */
    public function find(int $id): ?User
    {
        /** @var User|null */
        return $this->getModel()::find($id);
    }

    /**
     * Get all users with the "customer" role.
     *
     * @return \Illuminate\Database\Eloquent\Collection|User[]
     */
    public function getAllCustomers(): Collection
    {
        return User::role(UserRole::CUSTOMER()->value)->get();
    }

    protected function getMediaFields(): array
    {
        return ['image'];
    }

    protected function getModel(): string
    {
        return User::class;
    }

    protected function getMediaCollectionName(): string
    {
        return 'avatar';
    }

    private function handleUserMedia(User $user, string|UploadedFile|null $image): void
    {
        if ($image instanceof UploadedFile) {
            $this->mediaService->syncMedia($user, $image, 'avatar');
        } elseif (is_null($image) && $user->hasAvatar()) {
            $user->clearMediaCollection('avatar');
        }
    }
}
