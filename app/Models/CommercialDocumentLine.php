<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class CommercialDocumentLine extends Model{protected $guarded=['id'];public function document(){return $this->belongsTo(CommercialDocument::class,'commercial_document_id');}}
