@extends('modern.layouts.master')

@section('title', 'Packages disponibles')

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title text-whatsapp">Packages disponibles</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Packages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Succès!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreur!</strong> {{ session('error') }}
            @if(session('recharge_needed'))
                <br><br>
                <a href="{{ route('customer.wallet.index') }}" class="btn btn-warning btn-sm">
                    <i class="la la-credit-card-plus"></i> Recharger mon wallet
                </a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($currentSubscription)
        <div class="alert alert-info" role="alert">
            <strong>Abonnement actuel:</strong> {{ $currentSubscription->package->display_name }} 
            - Expire le {{ $currentSubscription->ends_at->format('d/m/Y') }}
            - {{ $currentSubscription->getRemainingMessages() }} messages restants
        </div>
        @endif

        <section id="packages-list">
            <div class="row">
                @foreach($packages as $package)
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card pricing-card h-100 package-{{ $package->name }}">
                        
                        <div class="card-body text-center">
                            <div class="pricing-plan-title">
                                <h5 class="package-title-{{ $package->name }}">{{ $package->display_name }}</h5>
                                @if($package->isTrial())
                                    <div class="pricing-price">
                                        <span class="price-currency-{{ $package->name }}">GRATUIT</span>
                                        <div class="pricing-duration">7 jours</div>
                                    </div>
                                @else
                                    <div class="pricing-price">
                                        @if($package->hasActivePromotion())
                                            {{-- Prix barré et badge promotion --}}
                                            <div class="original-price mb-1">
                                                <span class="text-decoration-line-through text-muted small">{{ number_format($package->price) }} XAF</span>
                                                <span class="badge bg-warning text-dark ms-1">-{{ $package->getPromotionalDiscountPercentage() }}%</span>
                                            </div>
                                            {{-- Prix promotionnel --}}
                                            <span class="price-currency-{{ $package->name }} promotional-price">{{ number_format($package->promotional_price) }}</span> <span class="price-currency-{{ $package->name }}">XAF</span>
                                        @else
                                            <span class="price-currency-{{ $package->name }}">{{ number_format($package->price) }}</span> <span class="price-currency-{{ $package->name }}">XAF</span>
                                        @endif
                                        <div class="pricing-duration">/ mois</div>
                                    </div>
                                    
                                    {{-- Affichage des dates de promotion --}}
                                    @if($package->hasActivePromotion() && $package->promotion_ends_at)
                                        <div class="promotion-info">
                                            <small class="text-warning">
                                                <i class="la la-clock"></i>
                                                Offre jusqu'au {{ $package->promotion_ends_at->format('d/m/Y') }}
                                            </small>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="text-muted pricing-description">
                                {{ $package->description }}
                            </div>

                            <ul class="list-unstyled pricing-features mt-4">
                                <li><i class="la la-check text-success me-2"></i>{{ number_format($package->messages_limit) }} messages</li>
                                <li><i class="la la-check text-success me-2"></i>{{ number_format($package->context_limit) }} tokens de contexte</li>
                                <li><i class="la la-check text-success me-2"></i>{{ $package->accounts_limit }} compte{{ $package->accounts_limit > 1 ? 's' : '' }} WhatsApp</li>
                                
                                @if($package->products_limit > 0)
                                    <li><i class="la la-check text-success me-2"></i>{{ $package->products_limit }} produit{{ $package->products_limit > 1 ? 's' : '' }}</li>
                                @else
                                    <li><i class="la la-times text-muted me-2"></i>Pas de produits</li>
                                @endif

                                @if($package->hasWeeklyReports())
                                    <li><i class="la la-check text-success me-2"></i>Rapports hebdomadaires</li>
                                @endif

                                @if($package->hasPrioritySupport())
                                    <li><i class="la la-check text-success me-2"></i>Support prioritaire</li>
                                @endif
                            </ul>
                        </div>

                        <div class="card-footer text-center">
                            @php
                                $hasUsedTrial = auth()->user()->subscriptions()
                                    ->whereHas('package', fn($q) => $q->where('name', 'trial'))
                                    ->exists();
                            @endphp
                            
                            @if($currentSubscription && $currentSubscription->package_id === $package->id)
                                <button class="btn btn-package-{{ $package->name }} w-100" disabled>
                                    <i class="la la-check-circle"></i> En cours
                                </button>
                            @elseif($package->isTrial() && $hasUsedTrial)
                                <button class="btn btn-outline-secondary w-100" disabled>
                                    Essai déjà utilisé
                                </button>
                            @else
                                @if($package->isTrial())
                                    <form method="POST" action="{{ route('customer.packages.subscribe', $package->id) }}" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir commencer l\'essai gratuit ?')">>
                                        @csrf
                                        <button type="submit" class="btn btn-package-{{ $package->name }} w-100">
                                            <i class="la la-gift"></i> Commencer l'essai
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-package-{{ $package->name }} w-100" 
                                            onclick="showCouponModal({{ $package->id }}, '{{ $package->display_name }}', {{ $package->getCurrentPrice() }})">
                                        <i class="la la-credit-card"></i> Souscrire
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
    </div>

<!-- Modal pour code promo -->
<div class="modal fade" id="couponModal" tabindex="-1" aria-labelledby="couponModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalLabel">
                    <i class="la la-ticket"></i> Souscription à <span id="packageName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="subscriptionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="price-summary mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Prix du package:</span>
                            <span class="fw-bold" id="originalPrice"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2" id="discountRow" style="display: none;">
                            <span class="text-success">Réduction (<span id="discountPercent"></span>):</span>
                            <span class="text-success fw-bold" id="discountAmount">-0 XAF</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total à payer:</span>
                            <span class="fw-bold text-primary fs-5" id="finalPrice"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="couponCode" class="form-label">
                            <i class="la la-gift"></i> Code promo (optionnel)
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="couponCode" name="coupon_code" 
                                   placeholder="Entrez votre code promo">
                            <button class="btn btn-outline-secondary" type="button" id="applyCouponBtn">
                                <i class="la la-check"></i> Appliquer
                            </button>
                        </div>
                        <div id="couponMessage" class="mt-2"></div>
                    </div>

                    <div class="alert alert-info">
                        <i class="la la-info-circle"></i> 
                        <strong>Votre solde actuel:</strong> {{ number_format(auth()->user()->wallet?->balance ?? 0) }} XAF
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="la la-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-success" id="subscribeBtn">
                        <i class="la la-credit-card"></i> Confirmer la souscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pricing-card {
    position: relative;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: transform 0.2s;
}

.pricing-card:hover {
    transform: translateY(-2px);
}

/* Package Trial - Bleu léger */
.package-trial {
    border-color: #5DADE2;
}

.package-title-trial {
    color: #2E86C1 !important;
}

.price-currency-trial {
    color: #2E86C1;
}

.btn-package-trial {
    background-color: #5DADE2;
    border-color: #5DADE2;
    color: white;
}

.btn-package-trial:hover {
    background-color: #2E86C1;
    border-color: #2E86C1;
}

/* Package Starter - Rouge */
.package-starter {
    border-color: #E74C3C;
}

.package-title-starter {
    color: #C0392B !important;
}

.price-currency-starter {
    color: #C0392B;
}

.btn-package-starter {
    background-color: #E74C3C;
    border-color: #E74C3C;
    color: white;
}

.btn-package-starter:hover {
    background-color: #C0392B;
    border-color: #C0392B;
}

/* Package Pro - Vert foncé */
.package-pro {
    border-color: #1E8449;
}

.package-title-pro {
    color: #186A3B !important;
}

.price-currency-pro {
    color: #186A3B;
}

.btn-package-pro {
    background-color: #1E8449;
    border-color: #1E8449;
    color: white;
}

.btn-package-pro:hover {
    background-color: #186A3B;
    border-color: #186A3B;
}

/* Package Business - Vert WhatsApp */
.package-business {
    border-color: #25D366;
}

.package-title-business {
    color: #1DAA48 !important;
}

.price-currency-business {
    color: #1DAA48;
}

.btn-package-business {
    background-color: #25D366;
    border-color: #25D366;
    color: white;
}

.btn-package-business:hover {
    background-color: #1DAA48;
    border-color: #1DAA48;
}


.pricing-price {
    font-size: 2rem;
    font-weight: 700;
    margin: 20px 0 10px;
}

.pricing-duration {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 400;
}

.pricing-features li {
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.pricing-features li:last-child {
    border-bottom: none;
}

/* Styles pour les prix promotionnels */
.promotional-price {
    animation: promotional-glow 2s ease-in-out infinite alternate;
}

@keyframes promotional-glow {
    from { opacity: 0.8; }
    to { opacity: 1; }
}

.promotion-info {
    margin-top: 10px;
}

.original-price {
    font-size: 0.8rem;
}

/* Badge promotion visible */
.badge.bg-warning {
    font-weight: bold;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.price-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.modal-body {
    padding: 1.5rem;
}

#couponMessage.alert {
    padding: 8px 12px;
    margin: 0;
}
</style>

<script>
let currentPackageId = null;
let currentPackagePrice = 0;
let appliedDiscount = 0;
let appliedCoupon = null;

function showCouponModal(packageId, packageName, packagePrice) {
    currentPackageId = packageId;
    currentPackagePrice = packagePrice;
    appliedDiscount = 0;
    appliedCoupon = null;
    
    // Mettre à jour le modal
    document.getElementById('packageName').textContent = packageName;
    document.getElementById('originalPrice').textContent = formatPrice(packagePrice);
    document.getElementById('finalPrice').textContent = formatPrice(packagePrice);
    document.getElementById('subscriptionForm').action = `/customer/packages/${packageId}/subscribe`;
    
    // Réinitialiser le formulaire
    document.getElementById('couponCode').value = '';
    document.getElementById('couponMessage').innerHTML = '';
    document.getElementById('discountRow').style.display = 'none';
    
    // Afficher le modal
    new bootstrap.Modal(document.getElementById('couponModal')).show();
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR').format(price) + ' XAF';
}

function updatePricing() {
    const finalPrice = currentPackagePrice - appliedDiscount;
    document.getElementById('finalPrice').textContent = formatPrice(finalPrice);
    
    if (appliedDiscount > 0) {
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('discountAmount').textContent = '-' + formatPrice(appliedDiscount);
        if (appliedCoupon && appliedCoupon.type === 'percentage') {
            document.getElementById('discountPercent').textContent = appliedCoupon.value + '%';
        } else {
            document.getElementById('discountPercent').textContent = 'Coupon';
        }
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
}

// Validation du coupon via AJAX
document.getElementById('applyCouponBtn').addEventListener('click', function() {
    const couponCode = document.getElementById('couponCode').value.trim();
    const messageDiv = document.getElementById('couponMessage');
    const applyBtn = this;
    
    if (!couponCode) {
        showCouponMessage('Veuillez entrer un code promo.', 'warning');
        return;
    }
    
    // Désactiver le bouton pendant la requête
    applyBtn.disabled = true;
    applyBtn.innerHTML = '<i class="la la-spinner la-spin"></i> Vérification...';
    
    // Requête AJAX pour valider le coupon
    fetch('/api/coupons/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            coupon_code: couponCode,
            package_price: currentPackagePrice
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            appliedDiscount = data.discount_amount;
            appliedCoupon = data.coupon;
            updatePricing();
            showCouponMessage(
                `Code promo appliqué ! Vous économisez ${formatPrice(data.savings)}.`, 
                'success'
            );
            applyBtn.innerHTML = '<i class="la la-check"></i> Appliqué';
            document.getElementById('couponCode').disabled = true;
        } else {
            appliedDiscount = 0;
            appliedCoupon = null;
            updatePricing();
            showCouponMessage(data.message, 'danger');
            applyBtn.innerHTML = '<i class="la la-check"></i> Appliquer';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showCouponMessage('Erreur lors de la validation du coupon.', 'danger');
        applyBtn.innerHTML = '<i class="la la-check"></i> Appliquer';
    })
    .finally(() => {
        applyBtn.disabled = false;
    });
});

function showCouponMessage(message, type) {
    const messageDiv = document.getElementById('couponMessage');
    messageDiv.innerHTML = `<div class="alert alert-${type} alert-sm">${message}</div>`;
}

// Permettre la soumission en appuyant sur Entrée dans le champ coupon
document.getElementById('couponCode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('applyCouponBtn').click();
    }
});

// Réinitialiser le formulaire à la fermeture du modal
document.getElementById('couponModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('couponCode').disabled = false;
    document.getElementById('applyCouponBtn').innerHTML = '<i class="la la-check"></i> Appliquer';
});
</script>
@endsection