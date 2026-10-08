<?php
namespace App\Modules\SafetyShop\Stock\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use App\Modules\SafetyShop\Stock\Services\InventoryService;
use App\Modules\SafetyShop\Stock\Services\LedgerQuery;
use App\Services\PrivateDocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class StockController extends Controller
{
    private function companyId(Request $request): int
    {
        $user=$request->user();
        $companyId=$user->isSuperAdmin()?(int)($request->input('company_id')?:\App\Models\Company::where('is_active',true)->orderBy('id')->value('id')):(int)$user->company_id;
        abort_unless($companyId && $user->canAccessCompany($companyId),403);
        return $companyId;
    }
    private function movementsQuery(Request $r) { return (new LedgerQuery)->build($r); }
    public function create(Request $request)
    {
        $companyId=$this->companyId($request);
        $products=Product::where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get(); $locations=Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)->orderBy('name')->get(); $suppliers=Master::where('company_id',$companyId)->where('type','supplier')->where('is_active',true)->orderBy('name')->get(); $requestKey=(string)Str::uuid();
        return view('safety-shop.stock.form',compact('products','locations','suppliers','requestKey','companyId'));
    }
    public function store(Request $r, InventoryService $service, PrivateDocumentStorage $documents)
    {
        $companyId=$this->companyId($r);
        $data=$r->validate(['company_id'=>['required','integer',Rule::in([$companyId])],'request_key'=>'required|uuid','type'=>'required|in:receipt,issue,return,adjustment,transfer','product_id'=>['required','integer',Rule::exists('safety_shop_products','id')->where('company_id',$companyId)],'location_id'=>['required','integer',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','location')->where('is_active',true))],'destination_id'=>['nullable','required_if:type,transfer','integer',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','location')->where('is_active',true))],'supplier_id'=>['nullable','integer',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','supplier')->where('is_active',true))],'quantity'=>'required|integer|between:-1000000,1000000','movement_date'=>'required|date_format:Y-m-d|before_or_equal:today','reference'=>'nullable|string|max:100','recipient'=>'nullable|string|max:150','notes'=>'required|string|max:2000','supporting_document'=>$documents->validationRules()]);
        $path=null;
        if($file=$r->file('supporting_document')){
            $attachment=$documents->store($file,'stock_movements');
            $path=$attachment['attachment_path'];
            $data=array_merge($data,$attachment);
        }
        try{$movement=$service->post($data,$r->user()->id);}catch(\Throwable $e){$documents->delete($path);throw $e;}
        return redirect()->route('safety-shop.stock.index')->with('success','Stock movement #'.$movement->id.' posted.');
    }
    public function attachment(Movement $movement, PrivateDocumentStorage $documents)
    {
        abort_unless(request()->user()->canAccessCompany($movement->company_id ?: optional($movement->product)->company_id),403);
        abort_unless($documents->exists($movement->attachment_path),404);
        return $documents->download($movement->attachment_path,$movement->attachment_name);
    }
    public function index(Request $r) { $companyId=$this->companyId($r); $movements=$this->movementsQuery($r)->where('company_id',$companyId)->latest('id')->paginate(25)->withQueryString(); $products=Product::where('company_id',$companyId)->orderBy('name')->get(); $locations=Master::where('company_id',$companyId)->where('type','location')->orderBy('name')->get(); return view('safety-shop.stock.index',compact('movements','products','locations')); }

}
