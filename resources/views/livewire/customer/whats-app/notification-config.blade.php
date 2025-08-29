<div>
    <div>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-light border-info">
                    <div class="d-flex align-items-center">
                        <i class="la la-robot text-primary mr-3" style="font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1 text-primary"><strong>Agent IA : {{ $account->agent_name ?: $account->session_name }}</strong></h6>
                            <small class="text-muted">Session ID: {{ $account->session_id }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <form wire:submit="save">
                <div class="row">
                    <!-- Email Notifications -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch mb-3">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="enableEmailNotifications"
                                    class="form-check-input"
                                    id="enableEmailNotifications"
                                >
                                <label class="form-check-label" for="enableEmailNotifications">
                                    📧 Activer les notifications par email
                                </label>
                            </div>
                            
                            @if($enableEmailNotifications)
                                <div wire:transition>
                                    <label class="form-label">Adresse email</label>
                                    <input 
                                        type="email" 
                                        wire:model="notificationEmail"
                                        class="form-control @error('notificationEmail') is-invalid @enderror"
                                        placeholder="admin@monentreprise.com"
                                    >
                                    <small class="form-text text-muted">
                                        {{ __('Adresse de notification pour votre équipe') }}
                                    </small>
                                    @error('notificationEmail')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- WhatsApp Notifications -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch mb-3">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="enableWhatsappNotifications"
                                    class="form-check-input"
                                    id="enableWhatsappNotifications"
                                >
                                <label class="form-check-label" for="enableWhatsappNotifications">
                                    📱 Activer les notifications WhatsApp
                                </label>
                            </div>
                            
                            @if($enableWhatsappNotifications)
                                <div wire:transition>
                                    <label class="form-label">Numéro WhatsApp</label>
                                    <input 
                                        type="text" 
                                        wire:model="notificationWhatsappNumber"
                                        class="form-control @error('notificationWhatsappNumber') is-invalid @enderror"
                                        placeholder="+33612345678"
                                    >
                                    <small class="form-text text-muted">
                                        {{ __('Format international avec indicatif pays (ex: +33612345678)') }}<br>
                                        {{ __('Pour être sûr de votre numéro, rendez-vous sur votre appli WhatsApp, cliquez sur les 3 points en haut, cliquez sur paramètres, puis cliquez sur votre profil, vous verrez exactement votre numéro à utiliser !') }}
                                    </small>
                                    @error('notificationWhatsappNumber')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                @if($enableEmailNotifications || $enableWhatsappNotifications)
                    <div class="alert alert-light border-success" wire:transition>
                        <div class="d-flex align-items-center">
                            <i class="la la-check-circle text-success mr-2"></i>
                            <small class="text-muted mb-0">{{ __('Les notifications sont configurées et actives pour cet agent.') }}</small>
                        </div>
                    </div>
                @endif
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-secondary">
                        <i class="la la-arrow-left"></i> {{ __('Retour') }}
                    </a>
                    
                    <button type="submit" class="btn btn-whatsapp" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="la la-save"></i> {{ __('Enregistrer la configuration') }}</span>
                        <span wire:loading>
                            <i class="la la-spinner la-spin"></i> {{ __('Enregistrement...') }}
                        </span>
                    </button>
                </div>
            </form>
    </div>
    
    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" wire:transition>
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>