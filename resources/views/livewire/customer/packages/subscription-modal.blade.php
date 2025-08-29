<div>
    <!-- Modal pour souscription avec coupon -->
    <div class="modal fade" id="subscriptionModal-{{ $package->id }}" tabindex="-1" aria-labelledby="subscriptionModalLabel-{{ $package->id }}" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subscriptionModalLabel-{{ $package->id }}">
                        <i class="la la-ticket"></i> Souscription à {{ $package->display_name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Résumé des prix -->
                    <div class="price-summary mb-4" style="background: #f8f9fa; border-radius: 8px; padding: 15px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Prix du package:</span>
                            <span class="fw-bold">{{ number_format($this->originalPrice) }} XAF</span>
                        </div>
                        
                        @if ($couponApplied && $this->discountAmount > 0)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success">Réduction ({{ $this->discountPercentage }}%):</span>
                                <span class="text-success fw-bold">-{{ number_format($this->discountAmount) }} XAF</span>
                            </div>
                        @endif
                        
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total à payer:</span>
                            <span class="fw-bold text-primary fs-5">{{ number_format($this->finalPrice) }} XAF</span>
                        </div>
                    </div>

                    <!-- Champ code promo -->
                    <div class="mb-3">
                        <label for="couponCode-{{ $package->id }}" class="form-label">
                            <i class="la la-gift"></i> Code promo (optionnel)
                        </label>
                        
                        @if ($couponApplied)
                            <!-- Coupon appliqué -->
                            <div class="input-group">
                                <input type="text" class="form-control bg-success text-white" 
                                       value="{{ $couponCode }}" readonly>
                                <button class="btn btn-outline-danger" type="button" wire:click="removeCoupon">
                                    <i class="la la-times"></i> Supprimer
                                </button>
                            </div>
                        @else
                            <!-- Saisie du coupon -->
                            <input type="text" 
                                   class="form-control @error('couponCode') is-invalid @enderror" 
                                   id="couponCode-{{ $package->id }}"
                                   wire:model.live.debounce.500ms="couponCode"
                                   placeholder="Entrez votre code promo">
                        @endif
                        
                        @error('couponCode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        @if ($couponMessage)
                            <div class="alert alert-{{ $couponMessageType }} mt-2 mb-0" style="padding: 8px 12px;">
                                {{ $couponMessage }}
                            </div>
                        @endif
                    </div>

                    <!-- Info solde -->
                    <div class="alert alert-info">
                        <i class="la la-info-circle"></i> 
                        <strong>Votre solde actuel:</strong> {{ number_format(auth()->user()->wallet?->balance ?? 0) }} XAF
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="la la-times"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-success" wire:click="subscribe">
                        <i class="la la-credit-card"></i> Confirmer la souscription
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>