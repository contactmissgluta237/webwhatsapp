<div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <div class="card border">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ __('Personal Information') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('Full Name:') }}</strong> {{ $customer->first_name }} {{ $customer->last_name }}</p>
                    <p><strong>{{ __('Email:') }}</strong> {{ $customer->email }}</p>
                    <p><strong>{{ __('Phone:') }}</strong> {{ $customer->phone_number ?? 'N/A' }}</p>
                    <p><strong>{{ __('Registration Date:') }}</strong> {{ $customer->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>{{ __('Status:') }}</strong> 
                        @if($customer->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('Inactive') }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-2">
            <div class="card border">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ __('Wallet Summary') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('Current Balance:') }}</strong> {{ number_format($customer->wallet->balance ?? 0, 0, ',', ' ') }} FCFA</p>
                    <p><strong>{{ __('Total Recharged:') }}</strong> {{ number_format($customer->totalRecharged() ?? 0, 0, ',', ' ') }} FCFA</p>
                    <p><strong>{{ __('Total Withdrawn:') }}</strong> {{ number_format($customer->totalWithdrawn() ?? 0, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h5 class="mb-2">{{ __('Transaction Actions') }}</h5>
        </div>
        <div class="col-md-4 col-sm-12 mb-2">
            <a href="{{ route('admin.transactions.index', ['table-filters' => ['transaction_type' => 'recharge', 'customer_id' => $customer->id]]) }}" class="btn btn-whatsapp w-100">
                <i class="ti ti-arrow-right"></i> {{ __('View Recharges') }}
            </a>
        </div>
        <div class="col-md-4 col-sm-12 mb-2">
            <a href="{{ route('admin.transactions.index', ['table-filters' => ['transaction_type' => 'withdrawal', 'customer_id' => $customer->id]]) }}" class="btn btn-danger w-100">
                <i class="ti ti-arrow-left"></i> {{ __('View Withdrawals') }}
            </a>
        </div>
        <div class="col-md-4 col-sm-12 mb-2">
            <a href="{{ route('admin.transactions.index', ['table-filters' => ['customer_id' => $customer->id]]) }}" class="btn btn-info w-100">
                <i class="ti ti-list"></i> {{ __('View All Transactions') }}
            </a>
        </div>
    </div>
</div>