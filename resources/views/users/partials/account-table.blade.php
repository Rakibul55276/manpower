<div class="table-wrap"><table><thead><tr><th>User</th><th>Role / branch</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($accounts as $account)
<tr>
<td><strong>{{ $account->name }}</strong><span class="sub">{{ $account->username }}@if($account->company) · {{ $account->company->name }}@endif</span></td>
<td>@if($account->isSuperAdmin())<span class="badge">System Owner</span><span class="sub">Platform management</span>@elseif($account->isCompanyAdmin())<span class="badge">Company Admin</span><span class="sub">Both modules · all company branches</span>@elseif($account->moduleAccesses->isNotEmpty())@foreach($account->moduleAccesses as $access)<span class="badge">{{ $access->module==='safety-shop'?'Safety Shop':'Manpower' }} · {{ ucfirst($access->role) }}</span><span class="sub">{{ optional($access->branch)->name ?: 'No branch assigned' }}</span>@endforeach @else<span class="badge">{{ $account->role==='manager'?'Branch Manager':'Branch Issuer' }}</span><span class="sub">{{ optional($account->branch)->name ?: 'No branch assigned' }}</span>@endif</td>
<td><span class="badge {{ $account->is_active ? 'active' : 'inactive' }}">{{ $account->is_active ? 'Active' : 'Disabled' }}</span></td>
<td><div class="actions"><a class="btn secondary small" href="{{ route($userRoutePrefix.'.edit',$account) }}">Manage</a>@if($account->id !== auth()->id())<form method="POST" action="{{ route($userRoutePrefix.'.destroy',$account) }}" data-confirm="Remove this user account? Accounts referenced by transaction history cannot be deleted.">@csrf @method('DELETE')<button class="btn danger small" type="submit">Remove</button></form>@endif</div></td>
</tr>
@empty<tr><td colspan="4"><div class="empty">{{ $empty }}</div></td></tr>@endforelse
</tbody></table></div>
