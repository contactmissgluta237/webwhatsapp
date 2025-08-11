<div>
    <div class="card border mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">{{ __('Referral Earnings') }}</h5>
        </div>
        <div class="card-body">
            <p><strong>{{ __('Total referral earnings:') }}</strong> {{ number_format($totalReferralEarnings ?? 0, 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    @if($customer->customer && $customer->customer->referrer)
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">{{ __('Referrer of') }} {{ $customer->first_name }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>{{ __('Name:') }}</strong> {{ $customer->customer->referrer->user->full_name }}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('Email:') }}</strong> {{ $customer->customer->referrer->user->email }}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('Affiliation Code:') }}</strong> {{ $customer->customer->referrer->user->affiliation_code }}
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info mb-4">{{ __('This customer has not been referred by anyone else.') }}</div>
    @endif

    <h5 class="mb-3">{{ __('Clients Referred by') }} {{ $customer->first_name }}</h5>
    @if($referredUsers->isEmpty())
        <div class="alert alert-info">{{ __('This customer has not referred any other users.') }}</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Registration Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($referredUsers as $referredUser)
                        <tr>
                            <td>{{ $referredUser->first_name }} {{ $referredUser->last_name }}</td>
                            <td>{{ $referredUser->email }}</td>
                            <td>{{ $referredUser->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>