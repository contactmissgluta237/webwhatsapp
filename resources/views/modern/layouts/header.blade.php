<nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-semi-light bg-whatsapp navbar-shadow">
    <div class="navbar-wrapper">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item mobile-menu d-md-none mr-auto">
                    <a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i class="ft-menu font-large-1"></i></a>
                </li>
                <li class="nav-item">
                    <a class="navbar-brand" href="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->check() ? route('customer.dashboard') : '/') }}">
                        <img class="brand-logo" alt="Whatsapp Agent logo" src="{{ asset('modern/images/logo/whatsapp-logo.svg') }}">
                        <h3 class="brand-text">Whatsapp Agent</h3>
                    </a>
                </li>
                <li class="nav-item d-md-none">
                    <a class="nav-link open-navbar-container" data-toggle="collapse" data-target="#navbar-mobile"><i class="la la-ellipsis-v"></i></a>
                </li>
            </ul>
        </div>
        <div class="navbar-container content">
            <div class="collapse navbar-collapse" id="navbar-mobile">
                <ul class="nav navbar-nav mr-auto float-left">
                    <li class="nav-item d-none d-md-block">
                        <a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i class="ft-menu"></i></a>
                    </li>
                    <li class="nav-item d-none d-lg-block">
                        <a class="nav-link nav-link-expand" href="#"><i class="ficon ft-maximize"></i></a>
                    </li>
                </ul>
                <ul class="nav navbar-nav float-right">
                    @livewire('components.notification-header')

                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="pwa-install-header-btn" style="display: none;" title="Installer l'application">
                                <i class="ft-download"></i>
                            </a>
                        </li>
                    @endauth

                    <li class="dropdown dropdown-user nav-item">
                        <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
                            <span class="mr-1 user-name text-bold-700">{{ auth()->check() ? auth()->user()->first_name . ' ' . auth()->user()->last_name : 'Utilisateur' }}</span>
                            <span class="avatar avatar-online">
                                <img src="{{ asset('modern/images/portrait/small/avatar-s-19.png') }}" alt="avatar"><i></i>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            @if(auth()->check() && auth()->user()->isCustomer())
                                <a class="dropdown-item" href="#"><i class="la la-wallet"></i> Solde: {{ number_format(auth()->user()->wallet?->balance ?? 0, 0, ',', ' ') }} FCFA</a>
                                <div class="dropdown-divider"></div>
                            @endif
                            @if(auth()->check())
                                @if(auth()->user()->isAdmin())
                                    <a class="dropdown-item" href="{{ route('admin.profile.show') }}"><i class="ft-user"></i> {{ __('profile.my_profile') }}</a>
                                    <a class="dropdown-item" href="{{ route('admin.tickets.index') }}"><i class="ft-help-circle"></i> Support</a>
                                @else
                                    <a class="dropdown-item" href="{{ route('customer.profile.show') }}"><i class="ft-user"></i> {{ __('profile.my_profile') }}</a>
                                    <a class="dropdown-item" href="{{ route('customer.tickets.index') }}"><i class="ft-help-circle"></i> Support</a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="ft-power"></i> Déconnexion</button>
                                </form>
                            @endif
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>