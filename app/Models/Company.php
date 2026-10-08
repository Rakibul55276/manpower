<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Company extends Model
{
    protected $fillable = ['name', 'logo_path', 'company_code', 'location', 'registration_number', 'contact_person', 'phone', 'email', 'address', 'notes', 'is_active', 'subscription_status', 'subscription_started_at', 'subscription_expires_at', 'subscription_grace_until'];
    protected $casts = ['is_active' => 'boolean', 'subscription_started_at'=>'date', 'subscription_expires_at'=>'date', 'subscription_grace_until'=>'date'];
    public function employees() { return $this->hasMany(Employee::class); }
    public function branches() { return $this->hasMany(Branch::class); }
    public function users() { return $this->hasMany(User::class); }
    public function assignedVouchers() { return $this->hasMany(SaasVoucher::class, 'assigned_company_id'); }
    public function redeemedVouchers() { return $this->hasMany(SaasVoucher::class, 'redeemed_company_id'); }
    public function subscriptionState()
    {
        if (!$this->is_active || in_array($this->subscription_status, ['inactive', 'suspended'], true)) return strtoupper($this->subscription_status ?: 'inactive');
        $today = now()->startOfDay();
        if ($this->subscription_expires_at && $this->subscription_expires_at->gte($today)) return $this->subscription_status === 'trial' ? 'TRIAL' : 'ACTIVE';
        if ($this->subscription_grace_until && $this->subscription_grace_until->gte($today)) return 'GRACE';
        return 'EXPIRED';
    }
    public function hasApplicationAccess() { return in_array($this->subscriptionState(), ['ACTIVE','TRIAL','GRACE'], true); }
}
