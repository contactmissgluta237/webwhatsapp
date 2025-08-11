<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="card-title">{{ __('Customer details') }}: {{ $customer->first_name }} {{ $customer->last_name }}</h4>
            </div>

            <ul class="nav nav-tabs mb-4" id="customerDetailsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab == 'overview' ? 'active' : '' }}" 
                            wire:click="setActiveTab('overview')" type="button" role="tab">
                        {{ __('General Information') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab == 'referrals' ? 'active' : '' }}" 
                            wire:click="setActiveTab('referrals')" type="button" role="tab">
                        Référencement
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="customerDetailsTabContent">
                {{-- Overview Tab Content --}}
                <div class="tab-pane fade {{ $activeTab == 'overview' ? 'show active' : '' }}" role="tabpanel">
                    <livewire:admin.customer-details.overview :customer="$customer" />
                </div>

                {{-- Referrals Tab Content --}}
                <div class="tab-pane fade {{ $activeTab == 'referrals' ? 'show active' : '' }}" role="tabpanel">
                    <livewire:admin.customer-details.referrals :customer="$customer" />
                </div>
            </div>
        </div>
    </div>
</div>
