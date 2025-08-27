<!-- Menu Customer -->

{{-- 1. Tableau de Bord --}}
<li class="nav-item {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
    <a href="{{ route('customer.dashboard') }}">
        <i class="la la-home"></i>
        <span class="menu-title" data-i18n="Tableau de bord">{{ __('Dashboard') }}</span>
    </a>
</li>

{{-- 2. Agents WhatsApp --}}
<li class="nav-item has-sub {{ request()->is('customer/whatsapp*') ? 'open' : '' }}">
    <a href="#">
        <i class="la la-whatsapp"></i>
        <span class="menu-title" data-i18n="Agents Whatsapp">{{ __('Agents Whatsapp') }}</span>
    </a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.whatsapp.index') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.whatsapp.index') }}">{{ __('Liste des agents') }}</a>
        </li>
        <li class="{{ request()->routeIs('customer.whatsapp.create') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.whatsapp.create') }}">{{ __('Ajouter un agent') }}</a>
        </li>
    </ul>
</li>

{{-- 3. Packages --}}
<li class="nav-item has-sub {{ request()->is('customer/packages*') || request()->is('customer/subscriptions*') ? 'open' : '' }}">
    <a href="#">
        <i class="la la-gift"></i>
        <span class="menu-title" data-i18n="Packages">{{ __('Packages') }}</span>
    </a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.packages.*') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.packages.index') }}">{{ __('Packages') }}</a>
        </li>
        <li class="{{ request()->routeIs('customer.subscriptions.*') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.subscriptions.index') }}">{{ __('Mes Souscriptions') }}</a>
        </li>
    </ul>
</li>

{{-- 4. Produits --}}
<li class="nav-item has-sub {{ request()->is('customer/products*') ? 'open' : '' }}">
    <a href="#">
        <i class="la la-cubes"></i>
        <span class="menu-title" data-i18n="Produits">{{ __('Produits') }}</span>
    </a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.products.index') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.products.index') }}">{{ __('Liste') }}</a>
        </li>
        <li class="{{ request()->routeIs('customer.products.create') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.products.create') }}">{{ __('Nouveau') }}</a>
        </li>
    </ul>
</li>

{{-- 5. Mes Filleuls --}}
<li class="nav-item {{ request()->routeIs('customer.referrals.*') ? 'active' : '' }}">
    <a href="{{ route('customer.referrals.index') }}">
        <i class="la la-group"></i>
        <span class="menu-title" data-i18n="My Referrals">{{ __('Mes filleuls') }}</span>
    </a>
</li>

{{-- 6. Transactions --}}
<li class="nav-item has-sub {{ request()->is('customer/transactions*') ? 'open' : '' }}">
    <a href="#">
        <i class="la la-money"></i>
        <span class="menu-title" data-i18n="Transactions">{{ __('Transactions') }}</span>
    </a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.transactions.recharge') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.transactions.recharge') }}">{{ __('Recharger mon compte') }}</a>
        </li>
        <li class="{{ request()->routeIs('customer.transactions.withdrawal') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.transactions.withdrawal') }}">{{ __('Faire un retrait') }}</a>
        </li>
        <li class="{{ request()->routeIs('customer.transactions.index') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.transactions.index') }}">{{ __('Historique des transactions') }}</a>
        </li>
    </ul>
</li>

{{-- 7. Support et Aide --}}
<li class="nav-item has-sub {{ request()->routeIs('customer.tickets.*') ? 'open' : '' }}">
    <a href="#">
        <i class="la la-life-ring"></i>
        <span class="menu-title" data-i18n="Help & Support">{{ __('Support et Aide') }}</span>
    </a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.tickets.index') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.tickets.index') }}">{{ __('Voir mes tickets') }}</a>
        </li>
        <li class="{{ request()->routeIs('customer.tickets.create') ? 'active' : '' }}">
            <a class="menu-item" href="{{ route('customer.tickets.create') }}">{{ __('Ouvrir un ticket') }}</a>
        </li>
    </ul>
</li>

{{-- 8. Profil --}}
<li class="nav-item {{ request()->routeIs('customer.profile.*') ? 'active' : '' }}">
    <a href="{{ route('customer.profile.show') }}">
        <i class="la la-user"></i>
        <span class="menu-title" data-i18n="My Profile">{{ __('Mon Profil') }}</span>
    </a>
</li>
