@php
use App\Enums\TransactionMode;
@endphp

<div class="container-fluid">
    @if($success)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ $success }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-1">
        <label class="form-label">Type de Retrait <span class="text-danger">*</span></label>
        <div class="row">
            @foreach($withdrawalModes as $mode)
                <div class="col-md-6 mb-1 mb-md-0">
                    <div class="d-flex justify-content-between">
                        <input type="radio" class="btn-check mr-1" name="withdrawal_mode" id="withdrawal_mode_{{ $mode['value'] }}"
                               autocomplete="off" wire:model.live="withdrawal_mode" value="{{ $mode['value'] }}">
                        <label class="btn btn-outline-info w-100" for="withdrawal_mode_{{ $mode['value'] }}">
                            {{ $mode['label'] }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($withdrawal_mode === TransactionMode::MANUAL()->value)
        <livewire:admin.withdrawal.manual-withdrawal-form />
    @else
        <livewire:admin.withdrawal.automatic-withdrawal-form />
    @endif
</div>
