@extends('layouts.app')
@section('title','User accounts')
@section('description','Searchable, paginated platform and company user directory.')
@section('actions')<a class="btn" href="{{ route($userRoutePrefix.'.create') }}">Add user →</a>@endsection
@section('content')
<div class="card"><div class="card-body"><form class="filters" method="GET">
<div class="field search"><label for="search">Search users</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Name, username, or company"></div>
<div class="field"><label for="role">Role</label><select id="role" name="role"><option value="">All roles</option>@foreach(['super_admin'=>'System Owner','admin'=>'Company Admin','manager'=>'Manager','issuer'=>'Issuer'] as $value=>$label)<option value="{{ $value }}" {{ request('role')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<button class="btn secondary">Apply filters</button><a class="btn link" href="{{ route($userRoutePrefix.'.index') }}">Clear</a>
</form></div></div>
<div class="card"><div class="card-head"><div><h2>Account directory</h2><span class="sub">25 accounts per page</span></div><span class="badge">{{ number_format($accounts->total()) }} accounts</span></div>
@include('users.partials.account-table',['accounts'=>$accounts,'empty'=>'No user accounts match the selected filters.'])
@include('partials.pagination',['items'=>$accounts])
</div>
@endsection
