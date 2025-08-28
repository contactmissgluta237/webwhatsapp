<?php

namespace App\Livewire\Shared;

use App\Enums\UserRole;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileForm extends Component
{
    use WithFileUploads;

    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone_number = '';
    public $affiliation_code = '';
    public $referrals_count = 0;
    public $locale = '';

    protected $rules = [
        'locale' => 'required|string|in:en,fr',
    ];

    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    public $avatar = null;
    public $current_avatar_url = '';

    public $error = null;
    public $success = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number;
        $this->affiliation_code = $user->affiliation_code;
        $this->current_avatar_url = $user->avatar_url;
        $this->referrals_count = $user->referrals()->count();
        $this->locale = $user->locale ?? config('app.locale');
    }

    public function isAdmin(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->isAdmin();
    }

    public function isCustomer(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->isCustomer();
    }

    public function updateProfile(): void
    {
        $request = new UpdateProfileRequest;

        $rules = $this->isCustomer()
            ? ['first_name' => 'required|string|max:255', 'last_name' => 'required|string|max:255']
            : $request->rules();

        $this->validate($rules, $request->messages());

        try {
            /** @var User $user */
            $user = Auth::user();

            $updateData = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
            ];

            if ($this->isAdmin()) {
                $updateData['email'] = $this->email;
                $updateData['phone_number'] = $this->phone_number;
            }

            $user->update($updateData);

            $this->success = 'Profil mis à jour avec succès.';
            $this->error = null;
        } catch (\Exception $e) {
            logger()->error('Erreur mise à jour profil', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->error = 'Une erreur est survenue lors de la mise à jour du profil.';
            $this->success = null;
        }
    }

    public function updatePassword(): void
    {
        $request = new UpdatePasswordRequest;
        $this->validate($request->rules(), $request->messages());

        try {
            /** @var User $user */
            $user = Auth::user();

            if (! Hash::check($this->current_password, $user->password)) {
                $this->error = 'Le mot de passe actuel est incorrect.';

                return;
            }

            $user->update([
                'password' => Hash::make($this->password),
            ]);

            $this->resetPasswordFields();

            $this->success = 'Mot de passe mis à jour avec succès.';
            $this->error = null;
        } catch (\Exception $e) {
            logger()->error('Erreur mise à jour mot de passe', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->error = 'Une erreur est survenue lors de la mise à jour du mot de passe.';
            $this->success = null;
        }
    }

    public function updateAvatar(): void
    {
        $request = new UpdateAvatarRequest;
        $this->validate($request->rules(), $request->messages());

        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $this->avatar) {
                throw new \Exception('Aucun fichier avatar trouvé');
            }

            $user->clearMediaCollection('avatar');
            $user->addMedia($this->avatar->getRealPath())
                ->usingName($this->avatar->getClientOriginalName())
                ->usingFileName($this->avatar->hashName())
                ->toMediaCollection('avatar');

            $this->current_avatar_url = $user->fresh()->avatar_url;
            $this->avatar = null;

            $this->success = 'Photo de profil mise à jour avec succès.';
            $this->error = null;
        } catch (\Exception $e) {
            logger()->error('Erreur mise à jour avatar', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = 'Une erreur est survenue lors de la mise à jour de la photo: '.$e->getMessage();
            $this->success = null;
        }
    }

    public function removeAvatar(): void
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $user->clearMediaCollection('avatar');

            $this->current_avatar_url = $user->fresh()->avatar_url;

            $this->success = 'Photo de profil supprimée avec succès.';
            $this->error = null;
        } catch (\Exception $e) {
            logger()->error('Erreur suppression avatar', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->error = 'Une erreur est survenue lors de la suppression de la photo.';
            $this->success = null;
        }
    }

    public function copyReferralLink(): void
    {
        $this->dispatch('copy-to-clipboard', [
            'text' => route('register').'?referral_code='.$this->affiliation_code,
        ]);
    }

    private function resetPasswordFields(): void
    {
        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function render()
    {
        return view('livewire.shared.profile-form');
    }

    public function updateLocale(): void
    {
        $this->validate();

        try {
            /** @var User $user */
            $user = Auth::user();
            $user->update(['locale' => $this->locale]);

            session()->put('locale', $this->locale);
            app()->setLocale($this->locale);

            session()->flash('success', __('Language updated successfully.'));

            if ($user->hasRole(UserRole::ADMIN()->value)) {
                $this->redirectRoute('admin.profile.show');
            } else {
                $this->redirectRoute('customer.profile.show');
            }
        } catch (\Exception $e) {
            logger()->error('Erreur mise à jour langue', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->error = __('Error updating language.');
            $this->success = null;
        }
    }
}
