<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Packages;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(UserRole::ADMIN()->value);
    }

    public function rules(): array
    {
        // Essayer d'obtenir l'ID du package depuis la route ou depuis les données injectées
        $package = $this->route('package');
        $packageId = $package ? $package->id : $this->input('package_id');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('packages', 'name')->ignore($packageId)],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'messages_limit' => ['required', 'integer', 'min:0'],
            'context_limit' => ['required', 'integer', 'min:0'],
            'accounts_limit' => ['required', 'integer', 'min:1'],
            'products_limit' => ['required', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'is_recurring' => ['boolean'],
            'one_time_only' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],

            'promotional_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'promotion_starts_at' => ['nullable', 'date'],
            'promotion_ends_at' => ['nullable', 'date', 'after:promotion_starts_at'],
            'promotion_is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du package est obligatoire.',
            'name.unique' => 'Ce nom de package existe déjà.',
            'display_name.required' => 'Le nom d\'affichage est obligatoire.',
            'price.required' => 'Le prix est obligatoire.',
            'price.min' => 'Le prix doit être positif ou nul.',
            'promotional_price.lt' => 'Le prix promotionnel doit être inférieur au prix normal.',
            'promotion_ends_at.after' => 'La fin de promotion doit être après le début.',
            'messages_limit.required' => 'La limite de messages est obligatoire.',
            'accounts_limit.min' => 'Il faut au moins 1 compte autorisé.',
        ];
    }
}
