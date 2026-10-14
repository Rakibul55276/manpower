<?php

namespace App\Modules\DocumentLibrary\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use App\Modules\DocumentLibrary\Models\LibraryDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentLibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = LibraryDocument::with(['owner', 'uploader'])->latest('id');
        $this->scopeQuery($request, $query);
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('document_number', 'like', '%'.$search.'%')
                    ->orWhere('original_name', 'like', '%'.$search.'%');
            });
        }
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->user()->isSuperAdmin() && $request->filled('owner_id')) $query->where('owner_id', $request->owner_id);

        return view('document-library.index', [
            'documents' => $query->paginate(20)->withQueryString(),
            'categories' => tap(LibraryDocument::query(), fn($categories) => $this->scopeQuery($request, $categories))->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'users' => $request->user()->isSuperAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect(),
            'documentRoutePrefix' => $this->routePrefix($request),
            'documentModule' => $this->module($request),
        ]);
    }

    public function create(Request $request)
    {
        return view('document-library.form', [
            'document' => new LibraryDocument,
            'users' => $request->user()->isSuperAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect(),
            'branches' => $this->branches($request),
            'documentRoutePrefix' => $this->routePrefix($request),
            'documentModule' => $this->module($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateDocument($request, true);
        $ownerId = $request->user()->id;
        $branchId = $request->user()->isCompanyAdmin() ? ($data['branch_id'] ?? null) : $request->user()->activeBranchId($this->module($request));
        $file = $request->file('scan');
        $path = $file->store('document-library/'.$this->module($request).'/'.$request->user()->company_id.'/'.($branchId ?: 'company'), 'local');
        try {
            $document = LibraryDocument::create([
                'owner_id' => $ownerId,
                'uploaded_by' => $request->user()->id,
                'company_id' => $request->user()->company_id,
                'module' => $this->module($request),
                'branch_id' => $branchId,
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'issued_on' => $data['issued_on'] ?? null,
                'expires_on' => $data['expires_on'] ?? null,
                'notes' => $data['notes'] ?? null,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        ActivityLog::record('Uploaded library document', $document->title);

        return redirect()->route($this->routePrefix($request).'.show', $document)->with('success', 'Scanned document uploaded securely.');
    }

    public function show(Request $request, LibraryDocument $document)
    {
        $this->authorizeDocument($request, $document);
        $document->load(['owner', 'uploader']);

        return view('document-library.show', ['document'=>$document, 'documentRoutePrefix'=>$this->routePrefix($request), 'documentModule'=>$this->module($request)]);
    }

    public function edit(Request $request, LibraryDocument $document)
    {
        $this->authorizeDocument($request, $document);

        return view('document-library.form', [
            'document' => $document,
            'users' => $request->user()->isSuperAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect(),
            'branches' => $this->branches($request),
            'documentRoutePrefix' => $this->routePrefix($request),
            'documentModule' => $this->module($request),
        ]);
    }

    public function update(Request $request, LibraryDocument $document)
    {
        $this->authorizeDocument($request, $document);
        $data = $this->validateDocument($request, false);
        $document->update([
            'owner_id' => $request->user()->isSuperAdmin() ? ($data['owner_id'] ?? $document->owner_id) : $document->owner_id,
            'branch_id' => $request->user()->isCompanyAdmin() ? ($data['branch_id'] ?? null) : $document->branch_id,
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        ActivityLog::record('Updated library document', $document->title);

        return redirect()->route($this->routePrefix($request).'.show', $document)->with('success', 'Document details updated.');
    }

    public function view(Request $request, LibraryDocument $document)
    {
        $this->authorizeDocument($request, $document);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);
        ActivityLog::record('Viewed library document', $document->title);

        return response()->file(Storage::disk('local')->path($document->storage_path), [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Request $request, LibraryDocument $document)
    {
        $this->authorizeDocument($request, $document);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);
        ActivityLog::record('Downloaded library document', $document->title);

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    public function destroy(Request $request, LibraryDocument $document)
    {
        abort_unless($request->user()->isCompanyAdmin(), 403);
        $this->authorizeDocument($request, $document);
        $title = $document->title;
        $path = $document->storage_path;
        $document->delete();
        Storage::disk('local')->delete($path);
        ActivityLog::record('Deleted library document', $title);

        return redirect()->route($this->routePrefix($request).'.index')->with('success', 'Document deleted.');
    }

    private function authorizeDocument(Request $request, LibraryDocument $document)
    {
        abort_unless((int)$document->company_id === (int)$request->user()->company_id && $document->module === $this->module($request), 403);
        if($request->user()->isCompanyAdmin() || $request->user()->isSuperAdmin()){
            return;
        }
        abort_unless((int)$document->branch_id === (int)$request->user()->activeBranchId($this->module($request)), 403);
    }

    private function validateDocument(Request $request, $withFile)
    {
        return $request->validate([
            'owner_id' => [Rule::requiredIf($request->user()->isSuperAdmin()), 'nullable', 'integer', 'exists:users,id'],
            'branch_id' => [Rule::requiredIf($request->user()->isCompanyAdmin()), 'nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $request->user()->company_id)],
            'title' => 'required|string|max:180',
            'category' => 'nullable|string|max:100',
            'document_number' => 'nullable|string|max:100',
            'issued_on' => 'nullable|date|before_or_equal:today',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'notes' => 'nullable|string|max:2000',
            'scan' => [$withFile ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('document_library.max_upload_kb')],
        ]);
    }

    private function module(Request $request): string { return $request->route('document_module'); }
    private function routePrefix(Request $request): string { return $request->route('document_route_prefix'); }
    private function branches(Request $request) { return Branch::where('company_id', $request->user()->company_id)->where('is_active', true)->orderBy('name')->get(); }
    private function scopeQuery(Request $request, $query): void
    {
        $query->where('company_id', $request->user()->company_id)->where('module', $this->module($request));
        if (!$request->user()->isCompanyAdmin() && !$request->user()->isSuperAdmin()) $query->where('branch_id', $request->user()->activeBranchId($this->module($request)));
    }
}
