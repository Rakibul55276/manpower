<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Designation;
use App\Models\ActivityLog;
use App\Services\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class LookupController extends Controller
{
    private function type() { return request()->route('lookup'); }
    private function model() { return $this->type() === 'companies' ? new Company : new Designation; }
    private function writable() { abort_unless($this->type() !== 'companies' || auth()->user()->isAdmin(), 403); }
    public function index()
    {
        $type = $this->type();
        $query = $type === 'companies' ? Access::companies() : Designation::query();
        $items = $query->withCount('employees')->orderBy('name')->paginate(15);
        $canManage = $type === 'designations' || auth()->user()->isAdmin();
        return view('lookups.index', compact('type', 'items', 'canManage'));
    }
    public function store(Request $request)
    {
        $this->writable();
        $data = $request->validate(['name' => ['required', 'string', 'max:150', Rule::unique($this->type())]]);
        $item = $this->model()->create($data);
        ActivityLog::record('Created '.$this->type(), $item->name);
        return back()->with('success', 'Created successfully.');
    }
    public function update(Request $request, $id)
    {
        $this->writable(); $item = $this->model()->findOrFail($id);
        $data = $request->validate(['name' => ['required', 'string', 'max:150', Rule::unique($this->type())->ignore($id)], 'is_active' => 'required|boolean']);
        $item->update($data); ActivityLog::record('Updated '.$this->type(), $item->name);
        return back()->with('success', 'Updated successfully.');
    }
    public function destroy($id)
    {
        $this->writable(); $item = $this->model()->findOrFail($id);
        if ($item->employees()->exists()) { return back()->withErrors(['name' => 'This item has employees. Deactivate it instead.']); }
        $name = $item->name; $item->delete(); ActivityLog::record('Deleted '.$this->type(), $name);
        return back()->with('success', 'Deleted successfully.');
    }
}
