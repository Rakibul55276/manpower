<?php
namespace App\Modules\SafetyShop\Shared\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Stock\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class MasterController extends Controller
{
    protected $type;
    private const DIRECTORIES = [
        'category'=>'categories','supplier'=>'suppliers','location'=>'locations',
        'brand'=>'brands','size'=>'sizes','unit'=>'units','safety_standard'=>'safety-standards',
    ];
    public function index(Request $r)
    {
        $type=$this->type; $directory=self::DIRECTORIES[$type];
        $masters=Master::where('type',$type)->orderBy('name')->paginate(20)->withQueryString();
        return view('safety-shop.shared.master-directory',compact('masters','type','directory'));
    }
    public function create() { return $this->form(new Master(['type'=>$this->type,'is_active'=>true])); }
    public function edit(Master $master) { abort_unless($master->type===$this->type,404); return $this->form($master); }
    private function form(Master $editing)
    {
        $type=$this->type; $directory=self::DIRECTORIES[$type];
        return view('safety-shop.shared.master-form',compact('editing','type','directory'));
    }
    public function saveMaster(Request $r, Master $master=null)
    {
        $r->merge(['type'=>$this->type]);
        if ($this->type === 'category') $r->merge(['sku_prefix'=>strtoupper(trim((string)$r->input('sku_prefix')))]);
        $companyId=optional($master)->company_id ?: $r->input('company_id') ?: \App\Models\Company::where('is_active',true)->orderBy('id')->value('id');
        $r->merge(['company_id'=>$companyId]);
        $data=$r->validate([
            'company_id'=>'required|integer|exists:companies,id',
            'type'=>['required',Rule::in(array_keys(self::DIRECTORIES))],
            'name'=>['required','string','max:150',Rule::unique('safety_shop_masters')->where(fn($q)=>$q->where('company_id',$companyId)->where('type',$r->type))->ignore(optional($master)->id)],
            'sku_prefix'=>[$this->type==='category'?'required':'nullable','string','min:2','max:10','regex:/^[A-Z0-9]+$/',Rule::unique('safety_shop_masters')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','category'))->ignore(optional($master)->id)],
            'contact_person'=>'nullable|string|max:150','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:150',
            'vat_number'=>'nullable|digits:15','commercial_registration'=>'nullable|string|max:30','website'=>'nullable|url|max:150',
            'address'=>'nullable|string|max:500','is_active'=>'required|boolean'
        ]);
        if ($this->type !== 'category') unset($data['sku_prefix']);
        if ($this->type !== 'supplier') foreach(['contact_person','vat_number','commercial_registration','website'] as $field) unset($data[$field]);
        if ($master && $master->exists) { abort_unless($master->type===$data['type'],422); $master->update($data); } else { $master=Master::create($data); }
        ActivityLog::record('Saved safety shop '.$master->type,$master->name);
        return redirect()->route('safety-shop.'.self::DIRECTORIES[$master->type].'.index')->with('success','Saved successfully.');
    }

}
