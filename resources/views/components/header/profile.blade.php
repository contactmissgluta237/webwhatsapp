<li class="header-profile">
    <a aria-controls="profilecanvasRight" class="d-block head-icon"
        data-bs-target="#profilecanvasRight" data-bs-toggle="offcanvas" href="#"
        role="button">
        <img alt="avtar" class="b-r-50 h-35 w-35 bg-dark"
            src="{{ asset('assets/images/avtar/woman.jpg') }}">
    </a>

    <div aria-labelledby="profilecanvasRight" class="offcanvas offcanvas-end header-profile-canvas"
        id="profilecanvasRight" tabindex="-1" style="max-height: 320px;">
        <div class="offcanvas-body p-3">
            <ul class="m-0 p-0">
                <li class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex-center">
                        <span class="h-45 w-45 d-flex-center b-r-10">
                            <img alt="" class="img-fluid b-r-10"
                                src="{{ asset('assets/images/avtar/woman.jpg') }}">
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ auth()->user()->first_name}} {{ auth()->user()->last_name}}</h6>
                        <p class="f-s-12 mb-0 text-secondary">{{ auth()->user()->email }}</p>
                    </div>
                </li>

                @if(auth()->user()->isCustomer())
                    <li class="mb-3">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                            <i class="ti ti-wallet text-primary f-s-18"></i>
                            <div>
                                <p class="f-s-11 mb-0 text-muted">Solde disponible</p>
                                <h6 class="mb-0 text-primary">{{ number_format(auth()->user()->wallet?->balance ?? 0, 0, ',', ' ') }} FCFA</h6>
                            </div>
                        </div>
                    </li>
                @endif

                <li class="mb-2">
                    <a class="f-w-500 d-block rounded hover-bg-light" href="{{ route('profile') }}">
                        <i class="iconoir-user-love pe-2 f-s-18"></i>Mon Profile
                    </a>
                </li>

                <li class="mb-2">
                    <a class="f-w-500 d-block rounded hover-bg-light" href="#">
                        <i class="iconoir-help-circle pe-2 f-s-18"></i>Aide
                    </a>
                </li>

                <li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                        class="d-none">
                        @csrf
                    </form>
                    <a class="btn btn-light-danger btn-sm w-100" href="#"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ph-duotone ph-sign-out pe-2"></i>Se déconnecter
                    </a>
                </li>
            </ul>
        </div>
    </div>
</li>