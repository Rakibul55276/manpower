<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'Dashboard') · Manpower</title><link rel="stylesheet" href="{{ asset('css/manpower.css') }}"><link rel="stylesheet" href="{{ asset('css/navigation.css') }}"><link rel="stylesheet" href="{{ asset('css/analytics.css') }}">@yield('head')<script defer src="{{ asset('js/manpower.js') }}"></script></head>
<body>
<aside class="sidebar" id="sidebar"><a class="brand" href="{{ config('safety_shop.enabled') && auth()->user()->isSuperAdmin() ? route('safety-shop.index') : route('dashboard') }}"><span class="brand-mark">HE</span><span>Harbour Edge<small>SAFETY SHOP &amp; OPERATIONS</small></span></a>
<nav aria-label="Main navigation">
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="workspace" aria-expanded="true" aria-controls="nav-section-workspace"><span>Workspace</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-workspace" data-nav-section="workspace"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">◫</span> Overview</a></div>
@if(config('safety_shop.enabled') && auth()->user()->isCompanyAdmin())
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="safety-shop-stock" aria-expanded="true" aria-controls="nav-section-safety-shop-stock"><span>Safety shop</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-safety-shop-stock" data-nav-section="safety-shop-stock">
<a class="nav-link {{ request()->routeIs('safety-shop.stock.index')?'active':'' }}" href="{{ route('safety-shop.stock.index') }}"><span class="nav-icon">⇄</span> Movement ledger</a>
<a class="nav-link {{ request()->routeIs('safety-shop.stock.create')?'active':'' }}" href="{{ route('safety-shop.stock.create') }}"><span class="nav-icon">＋</span> New stock movement</a>
</div>
@endif
@if(config('safety_shop.enabled') && auth()->user()->isSuperAdmin())
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="safety-shop" aria-expanded="true" aria-controls="nav-section-safety-shop"><span>Safety shop</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-safety-shop" data-nav-section="safety-shop">
<a class="nav-link {{ request()->routeIs('safety-shop.index')?'active':'' }}" href="{{ route('safety-shop.index') }}"><span class="nav-icon">⌂</span> Safety Shop home</a>
<button class="nav-label nav-section-toggle nav-subgroup-toggle" type="button" data-nav-section-toggle="safety-shop-transactions" aria-expanded="true" aria-controls="nav-section-safety-shop-transactions"><span>Transactions</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section nav-subgroup" id="nav-section-safety-shop-transactions" data-nav-section="safety-shop-transactions">
<a class="nav-link {{ request()->routeIs('safety-shop.sales.index','safety-shop.sales.show')?'active':'' }}" href="{{ route('safety-shop.sales.index') }}"><span class="nav-icon">▤</span> Sales receipts</a>
<a class="nav-link {{ request()->routeIs('safety-shop.returns.*')?'active':'' }}" href="{{ route('safety-shop.returns.index') }}"><span class="nav-icon">↩</span> Customer returns</a>
@if(auth()->user()->canApprove())
<a class="nav-link {{ request()->routeIs('safety-shop.sales.create')?'active':'' }}" href="{{ route('safety-shop.sales.create') }}"><span class="nav-icon">▥</span> Barcode checkout</a>
@endif
<a class="nav-link {{ request()->routeIs('safety-shop.stock.index')?'active':'' }}" href="{{ route('safety-shop.stock.index') }}"><span class="nav-icon">⇄</span> Movement ledger</a>
@if(auth()->user()->canApprove())
<a class="nav-link {{ request()->routeIs('safety-shop.stock.create')?'active':'' }}" href="{{ route('safety-shop.stock.create') }}"><span class="nav-icon">＋</span> New stock movement</a>
@endif
</div>
<button class="nav-label nav-section-toggle nav-subgroup-toggle" type="button" data-nav-section-toggle="safety-shop-masters" aria-expanded="true" aria-controls="nav-section-safety-shop-masters"><span>Master data</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section nav-subgroup" id="nav-section-safety-shop-masters" data-nav-section="safety-shop-masters">
<a class="nav-link {{ request()->routeIs('safety-shop.products.*')?'active':'' }}" href="{{ route('safety-shop.products.index') }}"><span class="nav-icon">▦</span> Products & stock</a>
@foreach(['categories'=>'Categories','brands'=>'Brands','sizes'=>'Sizes / variants','units'=>'Stock units','safety-standards'=>'Safety standards','suppliers'=>'Suppliers','locations'=>'Locations','customers'=>'Customers'] as $feature=>$label)
<a class="nav-link {{ request()->routeIs('safety-shop.'.$feature.'.*')?'active':'' }}" href="{{ route('safety-shop.'.$feature.'.index') }}"><span class="nav-icon">◇</span> {{ $label }}</a>
@endforeach
</div>
<button class="nav-label nav-section-toggle nav-subgroup-toggle" type="button" data-nav-section-toggle="safety-shop-insights" aria-expanded="true" aria-controls="nav-section-safety-shop-insights"><span>Analytics & reports</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section nav-subgroup" id="nav-section-safety-shop-insights" data-nav-section="safety-shop-insights"><a class="nav-link {{ request()->routeIs('safety-shop.reports.*')?'active':'' }}" href="{{ route('safety-shop.reports.index') }}"><span class="nav-icon">◫</span> Reports & analytics</a></div>
</div>
@endif
@if(config('document_library.enabled'))
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="document-library" aria-expanded="true" aria-controls="nav-section-document-library"><span>Document library</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-document-library" data-nav-section="document-library"><a class="nav-link {{ request()->routeIs('document-library.*')?'active':'' }}" href="{{ route('document-library.index') }}"><span class="nav-icon">▧</span> Scanned documents</a></div>
@endif
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="rental" aria-expanded="true" aria-controls="nav-section-rental"><span>Rental manpower</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-rental" data-nav-section="rental">
<a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><span class="nav-icon">♙</span> Rental employees</a>
<a class="nav-link {{ request()->routeIs('timesheets.*') ? 'active' : '' }}" href="{{ route('timesheets.index') }}"><span class="nav-icon">◷</span> Rental timesheets</a>
<a class="nav-link {{ request()->routeIs('payrolls.*') ? 'active' : '' }}" href="{{ route('payrolls.index') }}"><span class="nav-icon">▤</span> Rental payroll</a>
</div>
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="own" aria-expanded="true" aria-controls="nav-section-own"><span>Own employees</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-own" data-nav-section="own">
<a class="nav-link {{ request()->routeIs('own-employees.*') ? 'active' : '' }}" href="{{ route('own-employees.index') }}"><span class="nav-icon">♙</span> Own employee directory</a>
<a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><span class="nav-icon">◷</span> Attendance & overtime</a>
<a class="nav-link {{ request()->routeIs('salaries.*') ? 'active' : '' }}" href="{{ route('salaries.index') }}"><span class="nav-icon">▤</span> Monthly salaries</a>
</div>
@if(config('invoicing.enabled') && auth()->user()->isSuperAdmin())
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="invoicing" aria-expanded="true" aria-controls="nav-section-invoicing"><span>Invoice system</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-invoicing" data-nav-section="invoicing">
<a class="nav-link {{ request()->routeIs('invoicing.index') || request()->routeIs('invoicing.show') ? 'active' : '' }}" href="{{ route('invoicing.index') }}"><span class="nav-icon">▤</span> Invoice directory</a>
<a class="nav-link {{ request()->routeIs('invoicing.create') ? 'active' : '' }}" href="{{ route('invoicing.create') }}"><span class="nav-icon">＋</span> New invoice</a>
<a class="nav-link {{ request()->routeIs('invoicing.customers.*') ? 'active' : '' }}" href="{{ route('invoicing.customers.index') }}"><span class="nav-icon">♙</span> Customers</a>
<a class="nav-link {{ request()->routeIs('invoicing.items.*') ? 'active' : '' }}" href="{{ route('invoicing.items.index') }}"><span class="nav-icon">◇</span> Products &amp; services</a>
<a class="nav-link {{ request()->routeIs('invoicing.settings.*') ? 'active' : '' }}" href="{{ route('invoicing.settings.index') }}"><span class="nav-icon">⚙</span> Invoice settings</a>
@if(auth()->user()->isSuperAdmin())<a class="nav-link {{ request()->routeIs('invoicing.design.*') ? 'active' : '' }}" href="{{ route('invoicing.design.index') }}"><span class="nav-icon">✦</span> Invoice design studio</a>@endif
</div>
@endif
@if(config('zatca.enabled') && auth()->user()->isSuperAdmin())
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="zatca" aria-expanded="true" aria-controls="nav-section-zatca"><span>ZATCA integration</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-zatca" data-nav-section="zatca"><a class="nav-link {{ request()->routeIs('zatca.*') ? 'active' : '' }}" href="{{ route('zatca.index') }}"><span class="nav-icon">◇</span> Integration center</a></div>
@endif
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="organization" aria-expanded="true" aria-controls="nav-section-organization"><span>Organization</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-organization" data-nav-section="organization">
<a class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}"><span class="nav-icon">▦</span> Companies</a>
<a class="nav-link {{ request()->routeIs('designations.*') ? 'active' : '' }}" href="{{ route('designations.index') }}"><span class="nav-icon">◇</span> Designations</a>
@if(auth()->user()->isSuperAdmin())
<a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="nav-icon">⚙</span> Users & company access</a>
@endif
@if(auth()->user()->canApprove())
<a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><span class="nav-icon">≡</span> Audit reports</a>
@if(auth()->user()->isSuperAdmin())<a class="nav-link {{ request()->routeIs('document-branding.*') ? 'active' : '' }}" href="{{ route('document-branding.edit') }}"><span class="nav-icon">▣</span> Document branding</a>@endif
@if(config('saas.saas_voucher_enabled') && auth()->user()->isSuperAdmin())<a class="nav-link {{ request()->routeIs('saas.*') ? 'active' : '' }}" href="{{ route('saas.vouchers.index') }}"><span class="nav-icon">◇</span> SaaS vouchers</a>@endif
@if(config('saas.saas_voucher_enabled') && !auth()->user()->isSuperAdmin())<a class="nav-link {{ request()->routeIs('subscription.*') ? 'active' : '' }}" href="{{ route('subscription.show') }}"><span class="nav-icon">◇</span> Subscription</a>@endif
@endif
</div>
</nav><div class="sidebar-note">A clear view of your people,<br>their hours, and their pay.<br><br>Currency: SAR · Riyadh time</div></aside>
<div class="app-shell"><header class="topbar"><button class="menu-toggle" type="button" data-menu-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Collapse navigation" title="Collapse navigation"><span aria-hidden="true">☰</span></button><div class="topbar-context"><strong>Your workforce, connected.</strong>{{ now()->format('l, d F Y') }}</div><div class="user-block"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><a href="{{ route('profile') }}">{{ auth()->user()->name }}<small>{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : (auth()->user()->role === 'admin' ? 'Admin Approver' : 'Company Manager') }}</small></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn link" type="submit">Sign out ↗</button></form></div></header>
<main class="main"><div class="page-head"><div><div class="eyebrow">@yield('eyebrow', 'Manpower workspace')</div><h1>@yield('title', 'Dashboard')</h1><p class="description">@yield('description')</p></div><div class="actions">@yield('actions')</div></div>
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')<footer class="footer"><span>Manpower · Workforce management</span><span>{{ auth()->user()->isSuperAdmin() ? 'All companies and branches' : (auth()->user()->isCompanyAdmin() ? 'Assigned company · all branches' : 'Assigned branch only') }}</span></footer></main></div></body></html>
