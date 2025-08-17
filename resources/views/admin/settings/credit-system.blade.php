@extends('modern.layouts.master')

@section('title', 'Credit System Settings')

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">Credit System</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.dashboard') }}"><i class="ti ti-home"></i>Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.settings.index') }}"><i class="ti ti-settings"></i>Settings</a>
                </li>
                <li class="breadcrumb-item active">Credit System</li>
            </ol>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Credit System Settings Card -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="ti ti-coins me-2"></i>
                    Credit System Configuration
                </h4>
                <p class="card-subtitle mb-0">
                    Manage AI message costs and automatic deduction settings
                </p>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.credit-system.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="message_cost" class="form-label">
                                    <i class="ti ti-message-circle me-1"></i>
                                    AI Message Cost
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="1000"
                                        class="form-control @error('message_cost') is-invalid @enderror" 
                                        id="message_cost" 
                                        name="message_cost" 
                                        value="{{ old('message_cost', $currentCost) }}"
                                        required
                                    >
                                    <span class="input-group-text">FCFA</span>
                                    @error('message_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Amount in FCFA that will be deducted from user's wallet for each AI response generated
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="ti ti-info-circle me-1"></i>
                                    System Information
                                </label>
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Current cost:</span>
                                            <span class="fw-bold">{{ number_format($currentCost, 2) }} FCFA</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Status:</span>
                                            <span class="badge bg-success">
                                                <i class="ti ti-check me-1"></i>Active
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Application:</span>
                                            <span class="text-primary">AI Messages Only</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">
                                    <i class="ti ti-info-circle me-2"></i>
                                    Credit System Operation
                                </h6>
                                <p class="mb-2">
                                    • Each AI response generated will automatically deduct the configured amount from the user's wallet
                                </p>
                                <p class="mb-2">
                                    • If the user does not have sufficient credit, no AI response will be generated
                                </p>
                                <p class="mb-2">
                                    • The system applies to real WhatsApp messages only (not simulations)
                                </p>
                                <p class="mb-0">
                                    • All deductions are recorded as internal transactions for traceability
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-2"></i>
                            Back to Settings
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection