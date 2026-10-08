@extends('layouts.app')
@section('title','Company subscription')
@section('eyebrow','Workspace access')
@section('description','Review the subscription state for '.$company->name.'.')
@section('content')
<div class="card"><div class="card-head"><div><h2>{{ $company->name }}</h2><span class="sub">{{ $company->company_code }}</span></div><span class="badge {{ $company->hasApplicationAccess()?'active':'inactive' }}">{{ $company->subscriptionState() }}</span></div><div class="card-body"><div class="stats"><div class="stat"><span>Subscription expires</span><strong>{{ optional($company->subscription_expires_at)->format('d M Y') ?: 'Not activated' }}</strong></div><div class="stat"><span>Grace access until</span><strong>{{ optional($company->subscription_grace_until)->format('d M Y') ?: 'Not available' }}</strong></div></div>
@if(auth()->user()->isCompanyAdmin())<form method="POST" action="{{ route('subscription.redeem') }}" style="margin-top:24px">@csrf<div class="field"><label>Voucher code *</label><input name="voucher" required maxlength="100" autocomplete="off" placeholder="VCH-XXXXXXXX-XXXXXXXX-XXXXXXXX" value="{{ old('voucher') }}"></div><div class="form-footer"><button class="btn">Redeem voucher</button></div></form>@else<p class="help" style="margin-top:24px">Only your company administrator can redeem a voucher.</p>@endif
</div></div>
@if($redemptions->isNotEmpty())
<div class="card"><div class="card-head"><h2>Redemption history</h2><span class="badge">Latest {{ $redemptions->count() }}</span></div><div class="table-wrap"><table><thead><tr><th>Voucher</th><th>Previous expiry</th><th>New expiry</th><th>Redeemed by</th><th>Date</th></tr></thead><tbody>@foreach($redemptions as $item)<tr><td>{{ $item->voucher->code_prefix }}…</td><td>{{ optional($item->previous_expiry)->format('d M Y') ?: 'None' }}</td><td><strong>{{ $item->new_expiry->format('d M Y') }}</strong></td><td>{{ $item->redeemer->name }}</td><td>{{ $item->redeemed_at->format('d M Y H:i') }}</td></tr>@endforeach</tbody></table></div></div>
@endif
@endsection
