<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SaasVoucher;
use App\Models\SaasVoucherRedemption;
use App\Services\SaasVoucherService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;

class SaasVoucherController extends Controller
{
    public function index()
    {
        abort_unless(config('saas.saas_voucher_enabled'), 404);
        $companies=Company::when(request('company_search'),fn($q,$search)=>$q->where(fn($match)=>$match->where('name','like','%'.$search.'%')->orWhere('company_code','like','%'.$search.'%')))->orderBy('name')->paginate(25,['*'],'company_page')->withQueryString();
        $vouchers=SaasVoucher::with(['assignedCompany','redeemedCompany','creator'])->latest()->paginate(25,['*'],'voucher_page')->withQueryString();
        $redemptions=SaasVoucherRedemption::with(['company','voucher'])->latest('redeemed_at')->paginate(25,['*'],'redemption_page')->withQueryString();
        return view('saas.platform', compact('companies','vouchers','redemptions'));
    }
    public function store(Request $request, SaasVoucherService $service)
    {
        abort_unless(config('saas.saas_voucher_enabled'), 404);
        if($request->filled('assigned_company_code'))$request->merge(['assigned_company_code'=>strtoupper(trim($request->assigned_company_code))]);
        $data=$request->validate([
            'duration_days'=>'required|integer|between:1,3650',
            'assigned_company_code'=>['nullable','string','max:30',Rule::exists('companies','company_code')],
            'assigned_company_id'=>['nullable','integer',Rule::exists('companies','id')],
            'valid_until'=>'nullable|date_format:Y-m-d|after_or_equal:today',
        ]);
        $data['assigned_company_id']=filled($data['assigned_company_code']??null)?Company::where('company_code',strtoupper($data['assigned_company_code']))->value('id'):($data['assigned_company_id']??null);
        unset($data['assigned_company_code']);
        $plain=$service->issue($data, $request->user());
        return redirect()->route('saas.vouchers.index')->with('new_voucher',$plain)->with('success','Voucher created. Copy it now; it will not be shown again.');
    }
    public function status(Request $request, Company $company)
    {
        abort_unless(config('saas.saas_voucher_enabled'), 404);
        $data=$request->validate(['subscription_status'=>'required|in:active,inactive,suspended,trial']);
        $company->update($data);
        ActivityLog::record($data['subscription_status']==='suspended'?'Removed company access':'Updated company access', $company->name.' · '.strtoupper($data['subscription_status']));
        return back()->with('success',$data['subscription_status']==='suspended'?'Company access removed. Users remain preserved but cannot enter company modules.':'Company subscription status updated.');
    }
}
