<div>
    <div class="content-body">
        <!-- Header avec actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Gestion des Coupons</h4>
                <small class="text-muted">Créez et gérez les codes promotionnels</small>
            </div>
            <button type="button" class="btn btn-success" wire:click="openCreateModal">
                <i class="la la-plus"></i> Nouveau Coupon
            </button>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Rechercher</label>
                        <input type="text" class="form-control" wire:model.live="search" placeholder="Code coupon...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select class="form-select" wire:model.live="filterStatus">
                            <option value="">Tous les statuts</option>
                            @foreach($couponStatuses as $status)
                                <option value="{{ $status->value }}">{{ $status->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" wire:model.live="filterType">
                            <option value="">Tous les types</option>
                            @foreach($couponTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary w-100" 
                                wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterType', '')">
                            <i class="la la-refresh"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table des coupons -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Valeur</th>
                                <th>Utilisation</th>
                                <th>Validité</th>
                                <th>Statut</th>
                                <th>Créé par</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coupons as $coupon)
                                <tr>
                                    <td>
                                        <code class="fs-6 fw-bold">{{ $coupon->code }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $coupon->type->label }}</span>
                                    </td>
                                    <td>
                                        @if($coupon->type === \App\Enums\CouponType::PERCENTAGE())
                                            {{ $coupon->value }}%
                                        @else
                                            {{ number_format($coupon->value) }} XAF
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">{{ $coupon->used_count }}/{{ $coupon->usage_limit }}</span>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar" style="width: {{ ($coupon->used_count / $coupon->usage_limit) * 100 }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @if($coupon->valid_from)
                                                Du {{ $coupon->valid_from->format('d/m/Y') }}
                                            @endif
                                            @if($coupon->valid_until)
                                                <br>Au {{ $coupon->valid_until->format('d/m/Y') }}
                                            @endif
                                            @if(!$coupon->valid_from && !$coupon->valid_until)
                                                Illimitée
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @if($coupon->status === \App\Enums\CouponStatus::ACTIVE())
                                            <span class="badge bg-success">{{ $coupon->status->label }}</span>
                                        @elseif($coupon->status === \App\Enums\CouponStatus::EXPIRED())
                                            <span class="badge bg-warning">{{ $coupon->status->label }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $coupon->status->label }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $coupon->creator->full_name ?? 'Système' }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    wire:click="openEditModal({{ $coupon->id }})"
                                                    title="Modifier">
                                                <i class="la la-edit"></i>
                                            </button>
                                            
                                            @if($coupon->status === \App\Enums\CouponStatus::ACTIVE())
                                                <button type="button" class="btn btn-outline-warning" 
                                                        wire:click="deactivateCoupon({{ $coupon->id }})"
                                                        wire:confirm="Êtes-vous sûr de vouloir désactiver ce coupon ?"
                                                        title="Désactiver">
                                                    <i class="la la-pause"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-outline-success" 
                                                        wire:click="activateCoupon({{ $coupon->id }})"
                                                        wire:confirm="Êtes-vous sûr de vouloir activer ce coupon ?"
                                                        title="Activer">
                                                    <i class="la la-play"></i>
                                                </button>
                                            @endif
                                            
                                            @if($coupon->used_count === 0)
                                                <button type="button" class="btn btn-outline-danger" 
                                                        wire:click="deleteCoupon({{ $coupon->id }})"
                                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce coupon ?"
                                                        title="Supprimer">
                                                    <i class="la la-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="la la-inbox la-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">Aucun coupon trouvé</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $coupons->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Création/Édition -->
    <div class="modal fade @if($showCreateModal || $showEditModal) show @endif" 
         style="display: @if($showCreateModal || $showEditModal) block @else none @endif" 
         tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($showCreateModal)
                            <i class="la la-plus"></i> Créer un nouveau coupon
                        @else
                            <i class="la la-edit"></i> Modifier le coupon
                        @endif
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModals"></button>
                </div>
                
                <form wire:submit.prevent="@if($showCreateModal) createCoupon @else updateCoupon @endif">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Code coupon -->
                            <div class="col-md-8">
                                <label class="form-label">Code coupon *</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       wire:model="code" 
                                       placeholder="Ex: SAVE20, WELCOME50">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        wire:click="generateRandomCode">
                                    <i class="la la-random"></i> Générer
                                </button>
                            </div>

                            <!-- Type -->
                            <div class="col-md-6">
                                <label class="form-label">Type *</label>
                                <select class="form-select @error('type') is-invalid @enderror" wire:model.live="type">
                                    @foreach($couponTypes as $couponType)
                                        <option value="{{ $couponType->value }}">{{ $couponType->label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Valeur -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Valeur *
                                    @if($type === 'percentage')
                                        <small class="text-muted">(0-100%)</small>
                                    @else
                                        <small class="text-muted">(XAF)</small>
                                    @endif
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control @error('value') is-invalid @enderror" 
                                           wire:model="value"
                                           step="@if($type === 'percentage') 0.01 @else 1 @endif"
                                           min="0.01"
                                           @if($type === 'percentage') max="100" @endif
                                           placeholder="@if($type === 'percentage') Ex: 20 @else Ex: 5000 @endif">
                                    <span class="input-group-text">
                                        @if($type === 'percentage') % @else XAF @endif
                                    </span>
                                </div>
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Limite d'utilisation -->
                            <div class="col-md-6">
                                <label class="form-label">Limite d'utilisation *</label>
                                <input type="number" class="form-control @error('usageLimit') is-invalid @enderror" 
                                       wire:model="usageLimit" min="1" max="10000">
                                @error('usageLimit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Statut -->
                            <div class="col-md-6">
                                <label class="form-label">Statut *</label>
                                <select class="form-select @error('status') is-invalid @enderror" wire:model="status">
                                    @foreach($couponStatuses as $couponStatus)
                                        <option value="{{ $couponStatus->value }}">{{ $couponStatus->label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Dates de validité -->
                            <div class="col-md-6">
                                <label class="form-label">Date de début (optionnel)</label>
                                <input type="date" class="form-control @error('validFrom') is-invalid @enderror" 
                                       wire:model="validFrom">
                                @error('validFrom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date de fin (optionnel)</label>
                                <input type="date" class="form-control @error('validUntil') is-invalid @enderror" 
                                       wire:model="validUntil">
                                @error('validUntil')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModals">
                            <i class="la la-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            @if($showCreateModal)
                                <i class="la la-save"></i> Créer le coupon
                            @else
                                <i class="la la-save"></i> Sauvegarder
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Backdrop -->
    @if($showCreateModal || $showEditModal)
        <div class="modal-backdrop fade show" wire:click="closeModals"></div>
    @endif
</div>

<style>
.modal.show {
    display: block !important;
}

.progress {
    background-color: #f8f9fa;
}

.progress-bar {
    background-color: #28a745;
}

code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    color: #495057;
    font-size: 0.875rem;
}
</style>