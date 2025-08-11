<?php

namespace App\Livewire\Admin\Users\Forms;

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Geography\Country;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class EditUserForm extends AbstractUserForm
{
    public User $user;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number;
        $this->is_active = $user->is_active;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->image = $user->hasAvatar() ? $user->avatar_url : null;

        // Initialize properties for the PhoneInput component
        if ($user->phone_number) {
            $this->full_phone_number = $user->phone_number;
            $this->parsePhoneNumberForEdit($user->phone_number);
        } else {
            $this->country_id = 1;
            $this->phone_number_only = '';
            $this->full_phone_number = '';
        }
    }

    private function parsePhoneNumberForEdit(string $phoneNumber): void
    {
        $countries = Country::active()->ordered()->get();

        foreach ($countries as $country) {
            if (str_starts_with($phoneNumber, $country->phone_code)) {
                $this->country_id = $country->id;
                $this->phone_number_only = substr($phoneNumber, strlen($country->phone_code));

                return;
            }
        }

        $defaultCountry = $countries->where('code', 'CM')->first();
        if ($defaultCountry) {
            $this->country_id = $defaultCountry->id;
            $this->phone_number_only = $phoneNumber;
        }
    }

    public function save()
    {
        $this->validate();

        $this->userService->updateUser(
            $this->user,
            $this->first_name,
            $this->last_name,
            $this->email,
            $this->phone_number,
            $this->password,
            $this->is_active,
            $this->selectedRoles,
            $this->image
        );

        session()->flash('success', 'Utilisateur mis à jour avec succès.');

        return redirect()->route('admin.users.index');
    }

    protected function customRequest(): FormRequest
    {
        $request = new UpdateUserRequest;
        $request->setUser($this->user);

        return $request;
    }

    public function render()
    {
        return view('livewire.admin.users.forms.edit-user-form');
    }
}
