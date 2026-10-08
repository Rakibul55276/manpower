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
    public function isAdmin() { return in_array($this->role, ['admin', 'super_admin'], true); }
    public function isSuperAdmin() { return $this->role === 'super_admin'; }
    public function isCompanyAdmin() { return $this->role === 'admin'; }
    public function isManager() { return $this->role === 'manager'; }
    public function canApprove() { return in_array($this->role, ['admin', 'super_admin'], true); }
    public function canAccessCompany($companyId) { return $this->isSuperAdmin() || (int) $this->company_id === (int) $companyId; }
    public function canAccessBranch($branchId) { return $this->isSuperAdmin() || ($this->isCompanyAdmin() ? Branch::whereKey($branchId)->where('company_id', $this->company_id)->exists() : (int) $this->branch_id === (int) $branchId); }

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
