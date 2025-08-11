<?php

namespace App\Livewire\Admin\Users\Forms;

use App\Http\Requests\Admin\CreateUserRequest;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserForm extends AbstractUserForm
{
    public function save()
    {
        $this->validate();

        $user = $this->userService->createUser(
            $this->first_name,
            $this->last_name,
            $this->email,
            $this->phone_number,
            $this->password,
            $this->is_active,
            $this->selectedRoles,
            $this->image
        );

        session()->flash('success', 'Utilisateur créé avec succès.');

        return redirect()->route('admin.users.index');
    }

    protected function customRequest(): FormRequest
    {
        return new CreateUserRequest;
    }

    public function render()
    {
        return view('livewire.admin.users.forms.create-user-form');
    }
}
