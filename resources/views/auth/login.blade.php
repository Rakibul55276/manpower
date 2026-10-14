<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ $module === 'safety-shop' ? 'Safety Shop' : 'Manpower' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ filemtime(public_path('images/favicon.png')) }}">
    <link rel="stylesheet" href="{{ asset('css/manpower.css') }}">
</head>
<body>
<div class="login-page">
    <div class="login-art">
        <div class="brand"><span class="brand-mark">{{ $module === 'safety-shop' ? 'S' : 'M' }}</span><span>{{ $module === 'safety-shop' ? 'Safety Shop' : 'Manpower' }}<small>{{ $module === 'safety-shop' ? 'STOCK &amp; SALES MANAGEMENT' : 'WORKFORCE MANAGEMENT' }}</small></span></div>
        <div class="login-copy">
            <div class="eyebrow" style="color:#b9edc9">{{ $module === 'safety-shop' ? 'Stock. Sales. Safety.' : 'People. Hours. Progress.' }}</div>
            <h1>{!! $module === 'safety-shop' ? 'Everything your safety<br>shop needs.' : 'Good work starts<br>with good people.' !!}</h1>
            <p>{{ $module === 'safety-shop' ? 'Manage inventory, barcode sales, VAT invoices, payments, and customer returns.' : 'One place to manage your workforce, track every hour, and keep payroll on course.' }}</p>
            <div class="login-tags">@if($module === 'safety-shop')<span>Inventory</span><span>VAT invoices</span><span>Returns</span>@else<span>Rental manpower</span><span>Own employees</span><span>Payroll</span>@endif</div>
        </div>
        <small style="color:#91b1b0;z-index:1">Built for the people behind the work.</small>
    </div>
    <div class="login-form-wrap">
        <div class="login-form">
            <div class="eyebrow">Welcome back</div>
            <h1>Sign in to {{ $module === 'safety-shop' ? 'Safety Shop' : 'Manpower' }}</h1>
            <p class="description">Enter your company code and company account.</p>
            @if($errors->any())<div class="notice error" style="margin-top:22px" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route($module === 'safety-shop' ? 'safety-shop.login.submit' : 'manpower.login.submit') }}">
                @csrf
                <div class="field"><label for="company_code">Company code</label><input id="company_code" name="company_code" type="text" value="{{ old('company_code') }}" required autofocus autocomplete="organization" placeholder="ACME" maxlength="30" style="text-transform:uppercase"></div>
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username" placeholder="admin, manager, or issuer">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter your password">
                </div>
                <button class="btn" type="submit">Sign in →</button>
            </form>
            <p class="login-bottom">Need access or a password reset?<br>Contact your Super Admin.</p>
        </div>
    </div>
</div>
</body>
</html>
