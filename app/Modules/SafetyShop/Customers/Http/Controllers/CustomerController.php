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
        if($search=trim((string)$request->get('search'))) $query->where(function($q)use($search){$q->where('name','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%');});
        if($request->get('status')==='active')$query->where('is_active',true);
        if($request->get('status')==='inactive')$query->where('is_active',false);
        $customers=$query->orderBy('name')->paginate(25)->withQueryString();
        return view('safety-shop.customers.index',compact('customers'));
    }
    public function create(){return view('safety-shop.customers.form',['customer'=>new Customer(['is_active'=>true])]);}
    public function edit(Customer $customer){return view('safety-shop.customers.form',compact('customer'));}
    public function store(Request $request){$customer=Customer::create($this->data($request));ActivityLog::record('Created safety shop customer',$customer->name);return redirect()->route('safety-shop.customers.index')->with('success','Customer added.');}
    public function update(Request $request,Customer $customer){$customer->update($this->data($request,$customer));ActivityLog::record('Updated safety shop customer',$customer->name);return redirect()->route('safety-shop.customers.index')->with('success','Customer updated.');}
    private function data(Request $request,Customer $customer=null)
    {
        if($request->filled('phone'))$request->merge(['phone'=>preg_replace('/[^0-9+]/','',$request->phone)]);
        return $request->validate(['name'=>'required|string|max:150','phone'=>['nullable','string','max:30',Rule::unique('safety_shop_customers','phone')->ignore(optional($customer)->id)],'email'=>'nullable|email|max:150','address'=>'nullable|string|max:500','is_active'=>'required|boolean']);
    }
}
