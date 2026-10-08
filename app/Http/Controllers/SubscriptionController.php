<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SaasVoucherRedemption;
use App\Services\SaasVoucherService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        abort_unless(config('saas.saas_voucher_enabled'), 404);
        abort_unless(!$request->user()->isSuperAdmin() && $request->user()->company, 403);
        $company=Company::findOrFail($request->user()->company_id);
        $redemptions=SaasVoucherRedemption::where('company_id',$company->id)->with(['voucher','redeemer'])->latest('redeemed_at')->limit(10)->get();
        return response()->view('saas.subscription', compact('company','redemptions'))->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
    }
    public function redeem(Request $request, SaasVoucherService $service)
    {
        abort_unless(config('saas.saas_voucher_enabled'), 404);
        abort_unless($request->user()->isCompanyAdmin() && $request->user()->company, 403);
        $data=$request->validate(['voucher'=>'required|string|max:100']);
        $redemption=$service->redeem($request->user()->company, $data['voucher'], $request->user());
        return redirect()->route('subscription.show')->with('success','Voucher redeemed. Access is active until '.$redemption->new_expiry->format('d M Y').'.');
    }
}
