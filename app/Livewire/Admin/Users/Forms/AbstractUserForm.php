<?php

namespace App\Livewire\Admin\Users\Forms;

use App\Services\Shared\Media\MediaServiceInterface;
use App\Services\User\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

abstract class AbstractUserForm extends Component
{
    use WithFileUploads;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone_number = '';
    public string $password = '';
    public bool $is_active = true;
    public array $selectedRoles = [];
    public string|UploadedFile|null $image = null;
    public bool $showPassword = false;

    // Propriétés pour le composant PhoneInput
    public ?int $country_id = 1;
    public string $phone_number_only = '';
    public string $full_phone_number = '';

    public $allRoles = [];

    protected UserService $userService;
    protected MediaServiceInterface $mediaService;

    protected $listeners = ['phoneUpdated'];

    public function boot(
        UserService $userService,
        MediaServiceInterface $mediaService
    ) {
        $this->userService = $userService;
        $this->mediaService = $mediaService;
        $this->allRoles = Role::orderBy('name')->pluck('name', 'name')->toArray();
    }

    public function rules(): array
    {
        // @phpstan-ignore-next-line
        return $this->customRequest()->rules();
    }

    public function messages(): array
    {
        return $this->customRequest()->messages();
    }

    public function getImagePreviewStyleProperty(): string
    {
        $image = $this->image;

        if ($image) {
            if (is_string(value: $image)) {
                $url = $image;
            } elseif ($image instanceof TemporaryUploadedFile) {
                $url = $image->temporaryUrl();
            } else {
                return '';
            }

            return "background-image: url('{$url}');";
        }

        return '';
    }

    abstract protected function customRequest(): FormRequest;

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    abstract public function save();

    public function phoneUpdated(array $data): void
    {
        if ($data['name'] === 'phone_number') {
            $this->country_id = $data['country_id'];
            $this->phone_number_only = $data['phone_number'];
            $this->full_phone_number = $data['value'];
            $this->phone_number = $this->full_phone_number;
        }
    }

    public function toggleShowPassword()
    {
        $this->showPassword = ! $this->showPassword;
    }

    public function render()
    {
        return view('livewire.admin.users.forms.user-form');
    }
}
