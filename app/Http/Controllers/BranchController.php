<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Company $company)
    {
        $branches = $company->branches()->withCount(['employees', 'managers'])->orderBy('name')->paginate(20);
        return view('branches.index', compact('company', 'branches'));
    }

    public function create(Company $company) { return view('branches.form', ['company'=>$company, 'branch'=>new Branch(['is_active'=>true])]); }

    public function store(Request $request, Company $company)
    {
        $branch = $company->branches()->create($this->data($request, $company));
        ActivityLog::record('Created branch', $company->name.' · '.$branch->name);
        return redirect()->route('companies.branches.index', $company)->with('success', 'Branch created.');
    }

    public function edit(Company $company, Branch $branch) { $this->owned($company, $branch); return view('branches.form', compact('company', 'branch')); }

    public function update(Request $request, Company $company, Branch $branch)
    {
        $this->owned($company, $branch); $branch->update($this->data($request, $company, $branch));
        ActivityLog::record('Updated branch', $company->name.' · '.$branch->name);
        return redirect()->route('companies.branches.index', $company)->with('success', 'Branch updated.');
    }

    public function destroy(Company $company, Branch $branch)
    {
        $this->owned($company, $branch);
        if ($branch->employees()->exists() || $branch->managers()->exists()) return back()->withErrors(['branch'=>'This branch has employees or a manager. Deactivate it instead.']);
        $name=$branch->name; $branch->delete(); ActivityLog::record('Deleted branch', $company->name.' · '.$name);
        return back()->with('success', 'Branch deleted.');
    }

    private function owned(Company $company, Branch $branch) { abort_unless((int)$branch->company_id === (int)$company->id, 404); }
    private function data(Request $request, Company $company, Branch $branch = null)
    {
        return $request->validate([
            'name'=>['required','string','max:150',Rule::unique('branches')->where('company_id',$company->id)->ignore(optional($branch)->id)],
            'code'=>['required','alpha_dash','max:50',Rule::unique('branches')->where('company_id',$company->id)->ignore(optional($branch)->id)],
            'location'=>'required|string|max:150', 'contact_person'=>'nullable|string|max:150', 'phone'=>'nullable|string|max:50',
            'email'=>'nullable|email|max:150', 'address'=>'nullable|string|max:500', 'notes'=>'nullable|string|max:2000',
            'is_active'=>($branch ? 'required' : 'nullable').'|boolean',
        ]);
    }
}
