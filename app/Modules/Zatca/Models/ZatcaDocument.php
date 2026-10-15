<?php
namespace App\Modules\Zatca\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaDocument extends Model
{
    protected $guarded=['id'];
    protected $casts=['icv'=>'integer','validation_results'=>'array','request_payload'=>'array','response_payload'=>'array','submitted_at'=>'datetime','accepted_at'=>'datetime','next_retry_at'=>'datetime'];
    public function device(){return $this->belongsTo(ZatcaDevice::class,'device_id');}
}
