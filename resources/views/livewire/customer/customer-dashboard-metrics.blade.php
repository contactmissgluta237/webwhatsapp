<div>
    <!-- Metrics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-wallet fs-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0 text-white">{{ number_format($walletBalance, 0, ',', ' ') }} FCFA</h4>
                            <p class="mb-0 text-white-50">Solde Portefeuille</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-arrow-up-circle fs-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0 text-white">{{ number_format($totalRecharges, 0, ',', ' ') }} FCFA</h4>
                            <p class="mb-0 text-white-50">Total Recharges</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-arrow-down-circle fs-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0 text-white">{{ number_format($totalWithdrawals, 0, ',', ' ') }} FCFA</h4>
                            <p class="mb-0 text-white-50">Total Retraits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-clock fs-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0 text-white">{{ number_format($pendingTransactions) }}</h4>
                            <p class="mb-0 text-white-50">Transactions en Attente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row mb-4">
        <div class="col-xl-6 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-users-group fs-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0 text-white">{{ number_format($activeReferrals) }}</h4>
                            <p class="mb-0 text-white-50">Filleuls Actifs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-coins fs-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0 text-white">{{ number_format($commissionsEarned, 0, ',', ' ') }} FCFA</h4>
                            <p class="mb-0 text-white-50">Commissions Gagnées</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>