<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function companies() { return $this->belongsToMany(Company::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function moduleAccesses() { return $this->hasMany(UserModuleAccess::class); }
    public function isAdmin() { return in_array($this->role, ['admin', 'manager', 'super_admin'], true); }
    public function isSuperAdmin() { return $this->role === 'super_admin'; }
    public function isCompanyAdmin() { return $this->role === 'admin'; }
    public function moduleAccess(?string $module = null) { return $this->moduleAccesses()->where('module', $module ?: session('active_module', 'manpower'))->where('is_active', true)->first(); }
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) return true;
        if (!$this->moduleAccesses()->exists()) return in_array($this->role, ['manager', 'issuer'], true);
        return $this->moduleAccesses()->where('module', $module)->where('is_active', true)->exists();
    }
    public function moduleRole(?string $module = null): ?string
    {
        if ($this->isSuperAdmin()) return 'super_admin';
        if ($this->isCompanyAdmin()) return 'admin';
        if (!$this->moduleAccesses()->exists()) return $this->role;
        return optional($this->moduleAccess($module))->role;
    }
    public function activeBranchId(?string $module = null): ?int
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) return null;
        if (!$this->moduleAccesses()->exists()) return $this->branch_id ? (int)$this->branch_id : null;
        $branch = optional($this->moduleAccess($module))->branch_id;
        return $branch ? (int)$branch : null;
    }
    public function isManager() { return $this->moduleRole() === 'manager'; }
    public function isIssuer() { return $this->moduleRole() === 'issuer'; }
    public function isBranchUser() { return in_array($this->moduleRole(), ['manager', 'issuer'], true); }
    public function canOperateSafetyShop() { return $this->canAccessModule('safety-shop'); }
    public function canApprove() { return in_array($this->moduleRole(), ['admin', 'manager', 'super_admin'], true); }
    public function canAccessCompany($companyId) { return $this->isSuperAdmin() || (int) $this->company_id === (int) $companyId; }
    public function canAccessBranch($branchId) { return $this->isSuperAdmin() || ($this->isCompanyAdmin() ? Branch::whereKey($branchId)->where('company_id', $this->company_id)->exists() : (int) $this->activeBranchId() === (int) $branchId); }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'is_active', 'company_id', 'branch_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
