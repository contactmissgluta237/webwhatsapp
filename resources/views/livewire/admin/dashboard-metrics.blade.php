<div class="row mb-4">
    <!-- Metrics Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-none border-gray-light">
            <div class="card-content">
                <div class="card-body">
                    <div class="media d-flex">
                        <div class="media-body text-left">
                            <h3 class="text-whatsapp">{{ number_format($registeredUsers) }}</h3>
                            <h6>Personnes Inscrites</h6>
                        </div>
                        <div>
                            <i class="la la-users text-whatsapp font-large-2 float-right"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-1 mb-0">
                        <div class="progress-bar bg-whatsapp" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-none border-gray-light">
            <div class="card-content">
                <div class="card-body">
                    <div class="media d-flex">
                        <div class="media-body text-left">
                            <h3 class="text-danger">{{ number_format($totalWithdrawals, 0, ',', ' ') }} FCFA</h3>
                            <h6>Montant de Retrait</h6>
                        </div>
                        <div>
                            <i class="la la-arrow-down text-danger font-large-2 float-right"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-1 mb-0">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 65%" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-none border-gray-light">
            <div class="card-content">
                <div class="card-body">
                    <div class="media d-flex">
                        <div class="media-body text-left">
                            <h3 class="text-success">{{ number_format($totalRecharges, 0, ',', ' ') }} FCFA</h3>
                            <h6>Montant de Recharge</h6>
                        </div>
                        <div>
                            <i class="la la-arrow-up text-success font-large-2 float-right"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-1 mb-0">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card shadow-none border-gray-light">
            <div class="card-content">
                <div class="card-body">
                    <div class="media d-flex">
                        <div class="media-body text-left">
                            <h3 class="text-warning">{{ number_format($companyProfit, 0, ',', ' ') }} FCFA</h3>
                            <h6>Bénéfice de l'Entreprise</h6>
                        </div>
                        <div>
                            <i class="la la-chart-line text-warning font-large-2 float-right"></i>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-1 mb-0">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>