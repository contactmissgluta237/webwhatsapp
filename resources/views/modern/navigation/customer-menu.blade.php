<!-- Menu Customer -->
<li class="navigation-header">
    <span data-i18n="Home">{{ __('Home') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Home"></i>
</li>
<li class="nav-item {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
    <a href="{{ route('customer.dashboard') }}">
        <i class="la la-dashboard"></i>
        <span class="menu-title" data-i18n="Dashboard">{{ __('Dashboard') }}</span>
    </a>
</li>

<li class="navigation-header">
    <span data-i18n="My Account">{{ __('My Account') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="My Account"></i>
</li>
<li class="nav-item {{ request()->routeIs('customer.profile.*') ? 'active' : '' }}">
    <a href="{{ route('customer.profile.show') }}">
        <i class="la la-user-circle"></i>
        <span class="menu-title" data-i18n="My Profile">{{ __('profile.my_profile') }}</span>
    </a>
</li>
<li class="nav-item {{ request()->routeIs('customer.referrals.*') ? 'active' : '' }}">
    <a href="{{ route('customer.referrals.index') }}">
        <i class="la la-group"></i>
        <span class="menu-title" data-i18n="My Referrals">{{ __('My Referrals') }}</span>
    </a>
</li>

<li class="navigation-header">
    <span data-i18n="Transactions">{{ __('Transactions') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Transactions"></i>
</li>
<li class="nav-item has-sub {{ request()->routeIs('customer.transactions.index') || request()->routeIs('customer.transactions.internal') ? 'open' : '' }}">
    <a href="#"><i class="la la-receipt"></i><span class="menu-title" data-i18n="Transaction History">{{ __('Transaction History') }}</span></a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.transactions.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('customer.transactions.index') }}">{{ __('External Transactions') }}</a></li>
        <li class="{{ request()->routeIs('customer.transactions.internal') ? 'active' : '' }}"><a class="menu-item" href="{{ route('customer.transactions.internal') }}">{{ __('Account Movements') }}</a></li>
    </ul>
</li>
<li class="nav-item {{ request()->routeIs('customer.transactions.recharge') ? 'active' : '' }}">
    <a href="{{ route('customer.transactions.recharge') }}">
        <i class="la la-plus"></i>
        <span class="menu-title" data-i18n="Recharge Account">{{ __('Recharge Account') }}</span>
    </a>
</li>
<li class="nav-item {{ request()->routeIs('customer.transactions.withdrawal') ? 'active' : '' }}">
    <a href="{{ route('customer.transactions.withdrawal') }}">
        <i class="la la-minus"></i>
        <span class="menu-title" data-i18n="Withdraw Funds">{{ __('Withdraw Funds') }}</span>
    </a>
</li>

<li class="navigation-header">
    <span data-i18n="WhatsApp">{{ __('WhatsApp Automation') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="WhatsApp"></i>
</li>
<li class="nav-item has-sub {{ request()->routeIs('whatsapp.*') ? 'open' : '' }}">
    <a href="#"><i class="la la-whatsapp"></i><span class="menu-title" data-i18n="WhatsApp Management">{{ __('Gestion WhatsApp') }}</span></a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('whatsapp.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('whatsapp.index') }}">{{ __('Liste') }}</a></li>
        <li class="{{ request()->routeIs('whatsapp.create') ? 'active' : '' }}"><a class="menu-item" href="{{ route('whatsapp.create') }}">{{ __('Nouveau') }}</a></li>
    </ul>
</li>

<li class="navigation-header">
    <span data-i18n="Support">{{ __('Support') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Support"></i>
</li>
<li class="nav-item has-sub {{ request()->routeIs('customer.tickets.*') ? 'open' : '' }}">
    <a href="#"><i class="la la-life-ring"></i><span class="menu-title" data-i18n="Help & Support">{{ __('Help & Support') }}</span></a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('customer.tickets.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('customer.tickets.index') }}">{{ __('tickets.list_tickets') }}</a></li>
        <li class="{{ request()->routeIs('customer.tickets.create') ? 'active' : '' }}"><a class="menu-item" href="{{ route('customer.tickets.create') }}">{{ __('tickets.new_ticket') }}</a></li>
    </ul>
</li>
