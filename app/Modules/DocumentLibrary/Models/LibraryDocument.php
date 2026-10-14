<?php

namespace App\Modules\DocumentLibrary\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryDocument extends Model
{
    protected $table = 'document_library_files';
    protected $guarded = ['id'];
    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date', 'size_bytes' => 'integer'];

    protected static function booted()
    {
        static::creating(function ($document) {
            if ($document->company_id) return;
            $owner = \App\Models\User::find($document->owner_id);
            if ($owner) {
                $document->company_id = $owner->company_id;
                $document->branch_id = $document->branch_id ?: $owner->branch_id;
                $document->module = $document->module ?: 'manpower';
            }
        });
    }

    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function uploader() { return $this->belongsTo(\App\Models\User::class, 'uploaded_by'); }
    public function company() { return $this->belongsTo(\App\Models\Company::class); }
    public function branch() { return $this->belongsTo(\App\Models\Branch::class); }
}
