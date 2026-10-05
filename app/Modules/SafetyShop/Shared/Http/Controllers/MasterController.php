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
    public function index(Request $r)
    {
        $type=$this->type; $directory=['category'=>'categories','supplier'=>'suppliers','location'=>'locations'][$type];
        $masters=Master::where('type',$type)->orderBy('name')->paginate(20)->withQueryString();
        return view('safety-shop.'.$directory.'.index',compact('masters','type','directory'));
    }
    public function create() { return $this->form(new Master(['type'=>$this->type,'is_active'=>true])); }
    public function edit(Master $master) { abort_unless($master->type===$this->type,404); return $this->form($master); }
    private function form(Master $editing)
    {
        $type=$this->type; $directory=['category'=>'categories','supplier'=>'suppliers','location'=>'locations'][$type];
        return view('safety-shop.'.$directory.'.form',compact('editing','type','directory'));
    }
    public function saveMaster(Request $r, Master $master=null)
    {
        $r->merge(['type'=>$this->type]);
        $data=$r->validate(['type'=>'required|in:category,supplier,location','name'=>['required','string','max:150',Rule::unique('safety_shop_masters')->where('type',$r->type)->ignore(optional($master)->id)],'phone'=>'nullable|string|max:50','email'=>'nullable|email|max:150','address'=>'nullable|string|max:500','is_active'=>'required|boolean']);
        if ($master && $master->exists) { abort_unless($master->type===$data['type'],422); $master->update($data); } else { $master=Master::create($data); }
        ActivityLog::record('Saved safety shop '.$master->type,$master->name);
        return redirect()->route('safety-shop.'.['category'=>'categories','supplier'=>'suppliers','location'=>'locations'][$master->type].'.index')->with('success','Saved successfully.');
    }

}
