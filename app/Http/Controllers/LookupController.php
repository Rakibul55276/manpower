<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Designation;
use App\Models\ActivityLog;
use App\Services\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Services\TenantDatabase;
use RuntimeException;
use Illuminate\Validation\ValidationException;
class LookupController extends Controller
{
    private function type() { return request()->route('lookup'); }
    private function model() { return $this->type() === 'companies' ? new Company : new Designation; }
    private function writable() { abort_unless($this->type()==='companies' ? auth()->user()->isSuperAdmin() : auth()->user()->isCompanyAdmin(), 403); }
    private function itemQuery() { return $this->type()==='companies' ? Company::query() : Designation::where('company_id',auth()->user()->company_id); }
    public function index()
    {
        $type = $this->type();
        $query = $type === 'companies' ? Access::companies() : Designation::where('company_id',auth()->user()->company_id);
        $items = $query->withCount('employees')->orderBy('name')->paginate(15);
        $canManage = $type==='companies' ? auth()->user()->isSuperAdmin() : auth()->user()->isCompanyAdmin();
        return view('lookups.index', compact('type', 'items', 'canManage'));
    }
    public function create()
    {
        $this->writable(); $type = $this->type();
        return view('lookups.create', compact('type'));
    }
    public function store(Request $request)
    {
        $this->writable();
        $data = $request->validate($this->rules());
        if ($this->type()==='companies') {
            $tenant = app(TenantDatabase::class);
            try { $tenant->verify($data); }
            catch (RuntimeException $error) { throw ValidationException::withMessages(['db_name'=>$error->getMessage()]); }
            $data['db_password_encrypted'] = $tenant->encryptedPassword((string)$data['db_password']);
            $data['db_verified_at'] = now();
            unset($data['db_password']);
        }
        if ($this->type()==='companies' && config('saas.saas_voucher_enabled')) {
            $trialDays=(int)($data['trial_days'] ?? 0); unset($data['trial_days']);
            $data['subscription_status']=$trialDays > 0 ? 'trial' : 'inactive';
            if($trialDays > 0){$data['subscription_started_at']=today();$data['subscription_expires_at']=today()->addDays($trialDays);$data['subscription_grace_until']=today()->addDays($trialDays + max(0,(int)config('saas.grace_days',7)));}
        }
        unset($data['logo'],$data['remove_logo']);
        if($this->type()==='designations') $data['company_id']=auth()->user()->company_id;
        $item = $this->model()->create($data);
        if($this->type()==='companies') {
            try { app(TenantDatabase::class)->provision($item,$request->only('admin_name','admin_username','admin_email','admin_password')); }
            catch (\Throwable $error) { $item->delete(); report($error); throw ValidationException::withMessages(['db_name'=>'The database was verified, but company provisioning failed. Confirm the template is current and the database user has INSERT permission.']); }
        }
        if($this->type()==='companies' && $request->hasFile('logo')) $item->update(['logo_path'=>$request->file('logo')->store('company-branding/'.$item->id,'local')]);
        ActivityLog::record('Created '.$this->type(), $item->name);
        return back()->with('success', 'Created successfully.');
    }
    public function edit($id)
    {
        $this->writable();
        $type = $this->type(); $item = $this->itemQuery()->findOrFail($id);
        return view('lookups.edit', compact('type', 'item'));
    }
    public function update(Request $request, $id)
    {
        $this->writable(); $item = $this->itemQuery()->findOrFail($id);
        $data = $request->validate($this->rules($id, true)); $oldLogo=$this->type()==='companies'?$item->logo_path:null;
        if ($this->type()==='companies') {
            $tenant = app(TenantDatabase::class);
            $password = array_key_exists('db_password', $data) && $data['db_password'] !== null
                ? (string)$data['db_password']
                : $tenant->decryptedPassword($item->db_password_encrypted);
            try { $tenant->verify(array_merge($data, ['db_password'=>$password])); }
            catch (RuntimeException $error) { throw ValidationException::withMessages(['db_name'=>$error->getMessage()]); }
            if (array_key_exists('db_password', $data) && $data['db_password'] !== null) $data['db_password_encrypted']=$tenant->encryptedPassword($password);
            $data['db_verified_at']=now(); unset($data['db_password']);
        }
        unset($data['logo'],$data['remove_logo']); $item->update($data);
        if($this->type()==='companies' && $request->filled('admin_password')) {
            try { app(TenantDatabase::class)->provision($item,$request->only('admin_name','admin_username','admin_email','admin_password')); }
            catch (\Throwable $error) { report($error); throw ValidationException::withMessages(['admin_username'=>'The administrator could not be provisioned. Confirm the username/email is not already present in this company database.']); }
        }
        if($this->type()==='companies' && $request->boolean('remove_logo')){$item->update(['logo_path'=>null]);if($oldLogo)Storage::disk('local')->delete($oldLogo);$oldLogo=null;}
        if($this->type()==='companies' && $request->hasFile('logo')){$path=$request->file('logo')->store('company-branding/'.$item->id,'local');$item->update(['logo_path'=>$path]);if($oldLogo)Storage::disk('local')->delete($oldLogo);}
        ActivityLog::record('Updated '.$this->type(), $item->name);
        return back()->with('success', 'Updated successfully.');
    }
    public function destroy($id)
    {
        $this->writable(); $item = $this->itemQuery()->findOrFail($id);
        if ($item->employees()->exists() || ($this->type()==='companies' && ($item->branches()->exists() || $item->users()->exists() || $item->assignedVouchers()->exists() || $item->redeemedVouchers()->exists()))) { return back()->withErrors(['name' => 'This item has employees, branches, users, or voucher history. Deactivate it instead.']); }
        $name = $item->name; $item->delete(); ActivityLog::record('Deleted '.$this->type(), $name);
        return back()->with('success', 'Deleted successfully.');
    }
    private function rules($id = null, $updating = false)
    {
        $unique=Rule::unique($this->type())->ignore($id);
        if($this->type()==='designations') $unique->where(fn($query)=>$query->where('company_id',auth()->user()->company_id));
        $rules = ['name' => ['required', 'string', 'max:150', $unique]];
        if ($this->type() === 'companies') {
            $rules += [
                'location' => 'required|string|max:150',
                'registration_number' => 'nullable|string|max:100',
                'contact_person' => 'nullable|string|max:150',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:150',
                'address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'remove_logo' => 'nullable|boolean',
                'db_host' => 'required|string|max:190',
                'db_port' => 'required|integer|between:1,65535',
                'db_name' => ['required','string','max:190','regex:/^[A-Za-z0-9_$-]+$/'],
                'db_username' => 'required|string|max:190',
            ];
            $rules['db_password'] = $updating ? 'nullable|string|max:500' : 'present|nullable|string|max:500';
            if(config('saas.saas_voucher_enabled')) $rules['company_code']=['required','string','min:2','max:30','regex:/^[A-Z0-9][A-Z0-9_-]+$/',Rule::unique('companies')->ignore($id)];
            if(!$updating && config('saas.saas_voucher_enabled')) $rules['trial_days']='nullable|integer|between:0,365';
            if(!$updating) {
                $rules['admin_name']='required|string|max:150';
                $rules['admin_username']='required|string|min:3|max:50|regex:/^[A-Za-z0-9._-]+$/';
                $rules['admin_email']='required|email|max:150';
                $rules['admin_password']=['required','string','min:12','confirmed','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9@#$%^&*!?._-]).+$/'];
            } else {
                $rules['admin_name']='nullable|required_with:admin_password|string|max:150';
                $rules['admin_username']='nullable|required_with:admin_password|string|min:3|max:50|regex:/^[A-Za-z0-9._-]+$/';
                $rules['admin_email']='nullable|required_with:admin_password|email|max:150';
                $rules['admin_password']=['nullable','string','min:12','confirmed','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9@#$%^&*!?._-]).+$/'];
            }
        }
        if ($updating) { $rules['is_active'] = 'required|boolean'; }
        return $rules;
    }
    public function logo(Company $company)
    {
        abort_unless(auth()->user()->canAccessCompany($company->id),403);
        abort_unless($company->logo_path && Storage::disk('local')->exists($company->logo_path),404);
        return Storage::disk('local')->response($company->logo_path,null,['Cache-Control'=>'private, max-age=3600','X-Content-Type-Options'=>'nosniff']);
    }
}
