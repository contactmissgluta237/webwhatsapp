@if(!empty($systemAccounts))
<div class="row mb-4">
    @php
        $accountColors = ['bg-info', 'bg-success', 'bg-warning', 'bg-secondary'];
    @endphp
    @foreach($systemAccounts as $index => $account)
    <div class="col-xl-3 col-md-6">
        <div class="card {{ $accountColors[$index % count($accountColors)] }} text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-wallet fs-1"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-0 text-white">{{ number_format($account['balance'], 0, ',', ' ') }} FCFA</h4>
                        <p class="mb-0 text-white-50">{{ $account['type'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif