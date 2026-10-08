<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['company_id', 'name', 'code', 'location', 'contact_person', 'phone', 'email', 'address', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function managers() { return $this->hasMany(User::class)->where('role', 'manager'); }
}
