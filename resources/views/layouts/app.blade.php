<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'Dashboard') · Manpower</title><link rel="stylesheet" href="{{ asset('css/manpower.css') }}"><link rel="stylesheet" href="{{ asset('css/navigation.css') }}"><script defer src="{{ asset('js/manpower.js') }}"></script></head>
<body>
<aside class="sidebar" id="sidebar"><a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">m</span><span>manpower<small>WORKFORCE MANAGEMENT</small></span></a>
<nav aria-label="Main navigation">
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="workspace" aria-expanded="true" aria-controls="nav-section-workspace"><span>Workspace</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-workspace" data-nav-section="workspace"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">◫</span> Overview</a></div>
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
<button class="nav-label nav-section-toggle" type="button" data-nav-section-toggle="organization" aria-expanded="true" aria-controls="nav-section-organization"><span>Organization</span><span class="nav-section-arrow" aria-hidden="true">⌄</span></button>
<div class="nav-section" id="nav-section-organization" data-nav-section="organization">
<a class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}"><span class="nav-icon">▦</span> Companies</a>
<a class="nav-link {{ request()->routeIs('designations.*') ? 'active' : '' }}" href="{{ route('designations.index') }}"><span class="nav-icon">◇</span> Designations</a>
@if(auth()->user()->isSuperAdmin())
<a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="nav-icon">⚙</span> Users & company access</a>
@endif
@if(auth()->user()->canApprove())
<a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><span class="nav-icon">≡</span> Audit reports</a>
@endif
</div>
</nav><div class="sidebar-note">A clear view of your people,<br>their hours, and their pay.<br><br>Currency: SAR · Riyadh time</div></aside>
<div class="app-shell"><header class="topbar"><button class="menu-toggle" type="button" data-menu-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Collapse navigation" title="Collapse navigation"><span aria-hidden="true">☰</span></button><div class="topbar-context"><strong>Your workforce, connected.</strong>{{ now()->format('l, d F Y') }}</div><div class="user-block"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><a href="{{ route('profile') }}">{{ auth()->user()->name }}<small>{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : (auth()->user()->role === 'admin' ? 'Admin Approver' : 'Company Manager') }}</small></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn link" type="submit">Sign out ↗</button></form></div></header>
<main class="main"><div class="page-head"><div><div class="eyebrow">@yield('eyebrow', 'Manpower workspace')</div><h1>@yield('title', 'Dashboard')</h1><p class="description">@yield('description')</p></div><div class="actions">@yield('actions')</div></div>
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')<footer class="footer"><span>Manpower · Workforce management</span><span>{{ auth()->user()->isAdmin() ? 'All companies' : 'Access limited to your assigned companies' }}</span></footer></main></div></body></html>
