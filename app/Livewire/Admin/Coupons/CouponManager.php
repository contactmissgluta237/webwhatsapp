<?php

namespace App\Livewire\Admin\Coupons;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class CouponManager extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingCoupon = null;

    // Form properties
    public $code = '';
    public $type = 'percentage';
    public $value = '';
    public $usageLimit = 100;
    public $validFrom = '';
    public $validUntil = '';
    public $status = 'active';

    // Filters
    public $filterStatus = '';
    public $filterType = '';
    public $search = '';

    protected $rules = [
        'code' => 'required|string|min:3|max:20|regex:/^[A-Z0-9]+$/',
        'type' => 'required|in:percentage,fixed_amount',
        'value' => 'required|numeric|min:0.01',
        'usageLimit' => 'required|integer|min:1|max:10000',
        'validFrom' => 'nullable|date|after_or_equal:today',
        'validUntil' => 'nullable|date|after:valid_from',
        'status' => 'required|in:active,expired,used',
    ];

    protected $messages = [
        'code.required' => 'Le code coupon est obligatoire.',
        'code.regex' => 'Le code ne peut contenir que des lettres majuscules et des chiffres.',
        'type.required' => 'Le type de coupon est obligatoire.',
        'value.required' => 'La valeur du coupon est obligatoire.',
        'value.min' => 'La valeur doit être positive.',
        'usageLimit.required' => 'La limite d\'utilisation est obligatoire.',
        'usageLimit.min' => 'La limite doit être d\'au moins 1.',
        'usageLimit.max' => 'La limite ne peut pas dépasser 10 000.',
        'validFrom.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
        'validUntil.after' => 'La date de fin doit être après la date de début.',
    ];

    public function mount()
    {
        $this->validFrom = Carbon::now()->format('Y-m-d');
        $this->validUntil = Carbon::now()->addMonth()->format('Y-m-d');
    }

    public function render()
    {
        $coupons = Coupon::query()
            ->with('creator:id,first_name,last_name')
            ->when($this->search, fn ($q) => $q->where('code', 'LIKE', "%{$this->search}%"))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.coupons.coupon-manager', [
            'coupons' => $coupons,
            'couponTypes' => CouponType::cases(),
            'couponStatuses' => CouponStatus::cases(),
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(Coupon $coupon)
    {
        $this->editingCoupon = $coupon;
        $this->code = $coupon->code;
        $this->type = $coupon->type->value;
        $this->value = $coupon->value;
        $this->usageLimit = $coupon->usage_limit;
        $this->validFrom = $coupon->valid_from?->format('Y-m-d') ?? '';
        $this->validUntil = $coupon->valid_until?->format('Y-m-d') ?? '';
        $this->status = $coupon->status->value;
        $this->showEditModal = true;
    }

    public function createCoupon()
    {
        // Validation spéciale pour le code unique
        $this->validate([
            'code' => 'required|string|min:3|max:20|regex:/^[A-Z0-9]+$/|unique:coupons,code',
        ] + $this->rules);

        // Validation spéciale pour la valeur selon le type
        if ($this->type === 'percentage' && $this->value > 100) {
            $this->addError('value', 'Le pourcentage ne peut pas dépasser 100%.');

            return;
        }

        Coupon::create([
            'code' => strtoupper($this->code),
            'type' => CouponType::from($this->type),
            'value' => $this->value,
            'status' => CouponStatus::from($this->status),
            'usage_limit' => $this->usageLimit,
            'used_count' => 0,
            'valid_from' => $this->validFrom ? Carbon::parse($this->validFrom) : null,
            'valid_until' => $this->validUntil ? Carbon::parse($this->validUntil) : null,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Coupon créé avec succès !');
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function updateCoupon()
    {
        // Validation spéciale pour le code unique (sauf pour le coupon actuel)
        $this->validate([
            'code' => 'required|string|min:3|max:20|regex:/^[A-Z0-9]+$/|unique:coupons,code,'.$this->editingCoupon->id,
        ] + $this->rules);

        // Validation spéciale pour la valeur selon le type
        if ($this->type === 'percentage' && $this->value > 100) {
            $this->addError('value', 'Le pourcentage ne peut pas dépasser 100%.');

            return;
        }

        $this->editingCoupon->update([
            'code' => strtoupper($this->code),
            'type' => CouponType::from($this->type),
            'value' => $this->value,
            'status' => CouponStatus::from($this->status),
            'usage_limit' => $this->usageLimit,
            'valid_from' => $this->validFrom ? Carbon::parse($this->validFrom) : null,
            'valid_until' => $this->validUntil ? Carbon::parse($this->validUntil) : null,
        ]);

        session()->flash('success', 'Coupon modifié avec succès !');
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function deactivateCoupon(Coupon $coupon)
    {
        $coupon->update(['status' => CouponStatus::EXPIRED()]);
        session()->flash('success', 'Coupon désactivé avec succès !');
    }

    public function activateCoupon(Coupon $coupon)
    {
        $coupon->update(['status' => CouponStatus::ACTIVE()]);
        session()->flash('success', 'Coupon activé avec succès !');
    }

    public function deleteCoupon(Coupon $coupon)
    {
        if ($coupon->used_count > 0) {
            session()->flash('error', 'Impossible de supprimer un coupon qui a été utilisé.');

            return;
        }

        $coupon->delete();
        session()->flash('success', 'Coupon supprimé avec succès !');
    }

    public function generateRandomCode()
    {
        do {
            $this->code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        } while (Coupon::where('code', $this->code)->exists());
    }

    public function updatedType()
    {
        // Réinitialiser la valeur quand on change de type
        $this->value = '';
        $this->resetErrorBag('value');
    }

    public function resetForm()
    {
        $this->code = '';
        $this->type = 'percentage';
        $this->value = '';
        $this->usageLimit = 100;
        $this->validFrom = Carbon::now()->format('Y-m-d');
        $this->validUntil = Carbon::now()->addMonth()->format('Y-m-d');
        $this->status = 'active';
        $this->editingCoupon = null;
        $this->resetErrorBag();
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetForm();
    }
}
