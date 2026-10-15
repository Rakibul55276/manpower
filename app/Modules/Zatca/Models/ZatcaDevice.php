<?php
namespace App\Modules\Zatca\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaDevice extends Model
{
    protected $guarded=['id'];
    protected $hidden=['private_key_encrypted','renewal_private_key_encrypted','compliance_secret_encrypted','production_secret_encrypted'];
    protected $casts=['last_icv'=>'integer','certificate_expires_at'=>'datetime','last_connected_at'=>'datetime'];
    public function documents(){return $this->hasMany(ZatcaDocument::class,'device_id');}
}
