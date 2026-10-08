<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PrivateDocumentStorage
{
    public function validationRules(bool $required=false): array
    {
        return [$required?'required':'nullable','file','mimes:'.implode(',',config('private_documents.allowed_extensions',[])),'max:'.config('private_documents.max_size_kb',10240)];
    }

    private function disk()
    {
        return Storage::disk(config('private_documents.disk', 'local'));
    }

    public function store(UploadedFile $file, string $directoryKey): array
    {
        $directory=config('private_documents.directories.'.$directoryKey);
        if(!$directory) throw new \InvalidArgumentException('Unknown private document directory: '.$directoryKey);
        $path=$file->store($directory,config('private_documents.disk','local'));
        if(!$path) throw new \RuntimeException('The supporting document could not be stored.');
        return ['attachment_path'=>$path,'attachment_name'=>$file->getClientOriginalName(),'attachment_mime'=>$file->getMimeType(),'attachment_size'=>$file->getSize()];
    }

    public function delete(?string $path): void
    {
        if($path)$this->disk()->delete($path);
    }

    public function exists(?string $path): bool
    {
        return $path && $this->disk()->exists($path);
    }

    public function download(string $path, string $name)
    {
        return $this->disk()->download($path,$name,['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
