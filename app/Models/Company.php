<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Company extends Model
{
    protected $fillable = ['name', 'location', 'registration_number', 'contact_person', 'phone', 'email', 'address', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function employees() { return $this->hasMany(Employee::class); }
    public function branches() { return $this->hasMany(Branch::class); }
    public function users() { return $this->hasMany(User::class); }
}
