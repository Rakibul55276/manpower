<?php
namespace App\Modules\SafetyShop\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Sales\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query=Customer::query();
        if($search=trim((string)$request->get('search'))) $query->where(function($q)use($search){$q->where('name','like','%'.$search.'%')->orWhere('contact_person','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%')->orWhere('vat_number','like','%'.$search.'%')->orWhere('commercial_registration','like','%'.$search.'%');});
        if(in_array($request->get('type'),['retail','company'],true))$query->where('customer_type',$request->type);
        if($request->get('status')==='active')$query->where('is_active',true);
        if($request->get('status')==='inactive')$query->where('is_active',false);
        if(in_array($request->get('approval'),['pending','approved'],true))$query->where('approval_status',$request->approval);
        $customers=$query->orderBy('name')->paginate(25)->withQueryString();
        return view('safety-shop.customers.index',compact('customers'));
    }
    public function create(){return view('safety-shop.customers.form',['customer'=>new Customer(['customer_type'=>'retail','country_code'=>'SA','is_active'=>true])]);}
    public function edit(Customer $customer){return view('safety-shop.customers.form',compact('customer'));}
    public function store(Request $request){$customer=Customer::create($this->data($request)+['approval_status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()]);ActivityLog::record('Created safety shop customer',$customer->name);return redirect()->route('safety-shop.customers.index')->with('success','Customer added and approved.');}
    public function update(Request $request,Customer $customer){$customer->update($this->data($request,$customer));ActivityLog::record('Updated safety shop customer',$customer->name);return redirect()->route('safety-shop.customers.index')->with('success','Customer updated.');}
    public function approve(Request $request,Customer $customer){$customer->update(['approval_status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now(),'is_active'=>true]);ActivityLog::record('Approved safety shop customer',$customer->name);return back()->with('success','Customer approved and available in checkout.');}
    private function data(Request $request,Customer $customer=null)
    {
        if($request->filled('phone'))$request->merge(['phone'=>preg_replace('/[^0-9+]/','',$request->phone)]);
        $request->merge(['customer_type'=>$request->input('customer_type',$customer->customer_type??'retail'),'country_code'=>$request->input('country_code',$customer->country_code??'SA')]);
        return $request->validate([
            'customer_type'=>'required|in:retail,company','name'=>'required|string|max:150','contact_person'=>'nullable|required_if:customer_type,company|string|max:150',
            'phone'=>['nullable','string','max:30',Rule::unique('safety_shop_customers','phone')->ignore(optional($customer)->id)],'email'=>'nullable|email|max:150',
            'vat_number'=>'nullable|required_if:customer_type,company|digits:15','commercial_registration'=>'nullable|string|max:30',
            'address'=>'nullable|string|max:500','building_number'=>'nullable|string|max:20','street'=>'nullable|string|max:150','district'=>'nullable|string|max:100','city'=>'nullable|string|max:100','postal_code'=>'nullable|string|max:10','country_code'=>'required|string|size:2','is_active'=>'required|boolean'
        ]);
    }
}
