<?php

namespace App\Modules\DocumentLibrary\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryDocument extends Model
{
    protected $table = 'document_library_files';
    protected $guarded = ['id'];
    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date', 'size_bytes' => 'integer'];

    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function uploader() { return $this->belongsTo(\App\Models\User::class, 'uploaded_by'); }
}
