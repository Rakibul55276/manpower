<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModuleAccess extends Model
{
    protected $table = 'user_module_access';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
}
