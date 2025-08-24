@props(['affiliation_code', 'referrals_count', 'is_customer'])

<div class="card shadow-none border-gray-light">
    <div class="card-body">
        <h5 class="card-title d-flex align-items-center gap-2 mb-4">
            <i class="ti ti-share"></i>
            @if($is_customer)
                Mon parrainage
            @else
                Code de parrainage
            @endif
        </h5>
        
        <div class="text-center">
            @if($is_customer)
                <div class="mb-4">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="bg-primary-subtle rounded-circle p-3 me-3">
                            <i class="ti ti-users-group fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 text-primary">{{ $referrals_count }}</h3>
                            <p class="mb-0 text-muted">Filleuls</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Votre code de parrainage</label>
                <div class="input-group">
                    <input type="text" class="form-control text-center fw-bold fs-5" 
                           value="{{ $affiliation_code }}" readonly>
                    <button class="btn btn-whatsapp" type="button" 
                            onclick="navigator.clipboard.writeText('{{ $affiliation_code }}'); 
                                    this.innerHTML='<i class=\'ti ti-check\'></i>'; 
                                    setTimeout(() => this.innerHTML='<i class=\'ti ti-copy\'></i>', 2000)">
                        <i class="ti ti-copy"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Lien de parrainage</label>
                <div class="input-group">
                    <input type="text" class="form-control" 
                           value="{{ route('register') }}?referral_code={{ $affiliation_code }}" readonly>
                    <button class="btn btn-whatsapp" type="button" 
                            onclick="let link = '{{ route('register') }}?referral_code={{ $affiliation_code }}'; 
                                    navigator.clipboard.writeText(link);
                                    this.innerHTML='<i class=\'ti ti-check\'></i>'; 
                                    setTimeout(() => this.innerHTML='<i class=\'ti ti-copy\'></i>', 2000)">
                        <i class="ti ti-copy"></i>
                    </button>
                </div>
            </div>

            @if($is_customer)
                <div class="d-grid">
                    <a href="{{ route('customer.referrals.index') }}" class="btn btn-whatsapp">
                        <i class="ti ti-users-group me-2"></i>
                        {{ __('profile.view_my_referrals') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>