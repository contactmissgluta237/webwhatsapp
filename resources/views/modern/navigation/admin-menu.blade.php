<!-- Menu Admin -->
<li class="navigation-header">
    <span data-i18n="Home">{{ __('admin-menu.Home') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Home"></i>
</li>
<li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <a href="{{ route('admin.dashboard') }}">
        <i class="la la-dashboard"></i>
        <span class="menu-title" data-i18n="Dashboard">{{ __('admin-menu.Dashboard') }}</span>
    </a>
</li>

<li class="navigation-header">
    <span data-i18n="Management">{{ __('admin-menu.Management') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Management"></i>
</li>
<li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
    <a href="{{ route('admin.users.index') }}">
        <i class="la la-users"></i>
        <span class="menu-title" data-i18n="Users">{{ __('admin-menu.Users') }}</span>
    </a>
</li>
<li class="nav-item {{ request()->routeIs('admin.referrals.*') ? 'active' : '' }}">
    <a href="{{ route('admin.referrals.index') }}">
        <i class="la la-group"></i>
        <span class="menu-title" data-i18n="Referrals">{{ __('admin-menu.Referrals') }}</span>
    </a>
</li>
<li class="nav-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
    <a href="{{ route('admin.profile.show') }}">
        <i class="la la-user-circle"></i>
        <span class="menu-title" data-i18n="My Profile">{{ __('profile.my_profile') }}</span>
    </a>
</li>

<li class="navigation-header">
    <span data-i18n="Financial">{{ __('admin-menu.Financial') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Financial"></i>
</li>
<li class="nav-item has-sub {{ request()->routeIs('admin.system-accounts.*') ? 'open' : '' }}">
    <a href="#"><i class="la la-wallet"></i><span class="menu-title" data-i18n="System Accounts">{{ __('admin-menu.System Accounts') }}</span></a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('admin.system-accounts.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.system-accounts.index') }}">{{ __('admin-menu.List') }}</a></li>
        <li class="{{ request()->routeIs('admin.system-accounts.recharge') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.system-accounts.recharge') }}">{{ __('admin-menu.Recharge') }}</a></li>
        <li class="{{ request()->routeIs('admin.system-accounts.withdrawal') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.system-accounts.withdrawal') }}">{{ __('admin-menu.Withdrawal') }}</a></li>
    </ul>
</li>
<li class="nav-item has-sub {{ request()->routeIs('admin.transactions.*') ? 'open' : '' }}">
    <a href="#"><i class="la la-credit-card"></i><span class="menu-title" data-i18n="Transactions">{{ __('admin-menu.Transactions') }}</span></a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('admin.transactions.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.transactions.index') }}">{{ __('admin-menu.All transactions') }}</a></li>
        <li class="{{ request()->routeIs('admin.transactions.internal') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.transactions.internal') }}">{{ __('admin-menu.Internal transactions') }}</a></li>
        <li class="{{ request()->routeIs('admin.transactions.recharge') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.transactions.recharge') }}">{{ __('admin-menu.Recharge') }}</a></li>
        <li class="{{ request()->routeIs('admin.transactions.withdrawal') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.transactions.withdrawal') }}">{{ __('admin-menu.Withdrawal') }}</a></li>
    </ul>
</li>

<li class="navigation-header">
    <span data-i18n="Subscriptions">Souscriptions</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Subscriptions"></i>
</li>
<li class="nav-item has-sub {{ request()->routeIs('admin.packages.*') || request()->routeIs('admin.subscriptions.*') ? 'open' : '' }}">
    <a href="#"><i class="la la-gift"></i><span class="menu-title" data-i18n="Subscriptions">Souscriptions</span></a>
    <ul class="menu-content">
        <li class="{{ request()->routeIs('admin.packages.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.packages.index') }}">Packages</a></li>
        <li class="{{ request()->routeIs('admin.subscriptions.index') ? 'active' : '' }}"><a class="menu-item" href="{{ route('admin.subscriptions.index') }}">Toutes les souscriptions</a></li>
    </ul>
</li>

<li class="navigation-header">
    <span data-i18n="Support">{{ __('admin-menu.Support') }}</span>
    <i class="la la-ellipsis-h" data-toggle="tooltip" data-placement="right" title="Support"></i>
</li>
<li class="nav-item {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
    <a href="{{ route('admin.tickets.index') }}">
        <i class="la la-life-ring"></i>
        <span class="menu-title" data-i18n="Support Tickets">{{ __('tickets.support_tickets') }}</span>
    </a>
</li>


