<div class="table-wrap"><table><thead><tr><th>User</th><th>Role / branch</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($accounts as $account)
<tr>
<td><strong>{{ $account->name }}</strong><span class="sub">{{ $account->username }}</span></td>
<td><span class="badge">{{ $account->isSuperAdmin() ? 'Super Admin' : ($account->isCompanyAdmin() ? 'Company Admin' : 'Branch Manager') }}</span>@if($account->branch)<span class="sub">{{ $account->branch->name }}</span>@elseif($account->isManager())<span class="sub">No branch assigned</span>@else<span class="sub">{{ $account->isSuperAdmin() ? 'All companies and branches' : 'All company branches' }}</span>@endif</td>
<td><span class="badge {{ $account->is_active ? 'active' : 'inactive' }}">{{ $account->is_active ? 'Active' : 'Disabled' }}</span></td>
<td><div class="actions"><a class="btn secondary small" href="{{ route('users.edit',$account) }}">Manage</a>@if($account->id !== auth()->id())<form method="POST" action="{{ route('users.destroy',$account) }}" data-confirm="Remove this user account? Accounts referenced by transaction history cannot be deleted.">@csrf @method('DELETE')<button class="btn danger small" type="submit">Remove</button></form>@endif</div></td>
</tr>
@empty<tr><td colspan="4"><div class="empty">{{ $empty }}</div></td></tr>@endforelse
</tbody></table></div>
