<div>
    <div class="row match-height">
        {{-- Solde Portefeuille --}}
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body">
                        <div class="media d-flex">
                            <div class="media-body text-left">
                                <h3 class="info"><x-user-currency :amount="$walletBalance" /></h3>
                                <h6>Solde Portefeuille</h6>
                            </div>
                            <div>
                                <i class="la la-wallet info font-large-2 float-right"></i>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-1 mb-0 box-shadow-2">
                            <div class="progress-bar bg-gradient-x-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Package Actuel --}}
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body">
                        <div class="media d-flex">
                            <div class="media-body text-left">
                                <h3 class="success">{{ $activePackageName ?? 'Aucun package' }}</h3>
                                <h6>Package Actuel</h6>
                            </div>
                            <div>
                                <i class="la la-box success font-large-2 float-right"></i>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-1 mb-0 box-shadow-2">
                            <div class="progress-bar bg-gradient-x-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Date d'Expiration --}}
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body">
                        <div class="media d-flex">
                            <div class="media-body text-left">
                                <h3 class="info">{{ $packageExpirationDate ?? 'N/A' }}</h3>
                                <h6>Date d'Expiration</h6>
                            </div>
                            <div>
                                <i class="la la-calendar info font-large-2 float-right"></i>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-1 mb-0 box-shadow-2">
                            <div class="progress-bar bg-gradient-x-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body">
                        <div class="media d-flex">
                            <div class="media-body text-left">
                                @php
                                    $usagePercentage = $messagesLimit > 0 ? ($messagesUsed / $messagesLimit) * 100 : 0;
                                    $isLowMessages = $usagePercentage > 80;
                                @endphp
                                <h3 class="{{ $isLowMessages ? 'danger' : 'warning' }}">{{ number_format($messagesUsed) }}/{{ number_format($messagesLimit) }}</h3>
                                <h6>Messages Utilisés</h6>
                            </div>
                            <div>
                                <i class="la la-comments {{ $isLowMessages ? 'danger' : 'warning' }} font-large-2 float-right"></i>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-1 mb-0 box-shadow-2">
                            <div class="progress-bar {{ $isLowMessages ? 'bg-gradient-x-danger' : 'bg-gradient-x-warning' }}" role="progressbar" style="width: {{ min(100, $usagePercentage) }}%" aria-valuenow="{{ $usagePercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row match-height">
        {{-- Filleuls Actifs --}}
        <div class="col-xl-6 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body">
                        <div class="media d-flex">
                            <div class="media-body text-left">
                                <h3 class="info">{{ number_format($activeReferrals) }}</h3>
                                <h6>Filleuls Actifs</h6>
                            </div>
                            <div>
                                <i class="la la-users info font-large-2 float-right"></i>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-1 mb-0 box-shadow-2">
                            <div class="progress-bar bg-gradient-x-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Commissions Gagnées --}}
        <div class="col-xl-6 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body">
                        <div class="media d-flex">
                            <div class="media-body text-left">
                                <h3 class="success">{{ number_format($commissionsEarned, 0, ',', ' ') }} FCFA</h3>
                                <h6>Commissions Gagnées</h6>
                            </div>
                            <div>
                                <i class="la la-dollar success font-large-2 float-right"></i>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-1 mb-0 box-shadow-2">
                            <div class="progress-bar bg-gradient-x-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
