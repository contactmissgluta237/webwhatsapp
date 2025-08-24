@extends('modern.layouts.master')

@section('title', __('Mes filleuls'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">{{ __('Mes filleuls') }}</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">{{ __('Mes filleuls') }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            {{-- Total des gains (moved to no referrals yet section) --}}
        </div>
    </div>

    <div class="content-body">

@if(auth()->user()->referrals()->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <i class="ti ti-users fs-1 text-primary mb-2"></i>
                                <h6 class="text-muted">{{ __('Total Referrals') }}</h6>
                                <h4 class="fw-bold">{{ auth()->user()->referrals()->count() }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <i class="ti ti-coins fs-1 text-success mb-2"></i>
                                <h6 class="text-muted">{{ __('Earnings this month') }}</h6>
                                <h4 class="fw-bold text-success"><x-user-currency :amount="0" /></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <i class="ti ti-trending-up fs-1 text-info mb-2"></i>
                                <h6 class="text-muted">{{ __('Active Referrals') }}</h6>
                                <h4 class="fw-bold text-info">{{ auth()->user()->referrals()->count() }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <i class="ti ti-share fs-1 text-warning mb-2"></i>
                                <h6 class="text-muted">{{ __('My Code') }}</h6>
                                <h4 class="fw-bold text-warning">{{ auth()->user()->affiliation_code }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <x-session-alerts />
        <div class="card border-gray-light shadow-none">
            <div class="card-body">
                @if(auth()->user()->referrals()->count() > 0)
                    @livewire('customer.referral-data-table')
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <span class="text-muted d-block">{{ __('Total des gains') }}</span>
                            <h4 class="fw-bold text-success mb-0">
                                {{ number_format(0, 0, ',', ' ') }} FCFA
                            </h4>
                        </div>
                        <i class="ti ti-users-off fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted mb-3">{{ __('No referrals yet') }}</h5>
                        <p class="text-muted mb-4">
                            {{ __('Share your referral code %s to start referring users and earn commissions.', ['%s' => auth()->user()->affiliation_code]) }}
                        </p>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" 
                                           value="{{ route('register') }}?ref={{ auth()->user()->affiliation_code }}" 
                                           readonly id="referralLink">
                                    <button class="btn btn-whatsapp" type="button" onclick="copyReferralLink()">
                                        <i class="ti ti-copy"></i> {{ __('Copy') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

    </div> <!-- End content-body -->

@if(auth()->user()->referrals()->count() == 0)
@push('scripts')
<script>
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        
        // Toast notification
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-success border-0 position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 1055;';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    {{ __('Referral link copied!') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
        
    } catch (err) {
        console.error('Erreur lors de la copie:', err);
    }
}
</script>
@endpush
@endif
@endsection