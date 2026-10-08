<?php
namespace App\Modules\SafetyShop\Shared\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Modules\SafetyShop\Shared\Models\Master;
use Illuminate\Validation\Rule;
class ReceiptSettingController extends Controller
{
    public function edit(){return view('safety-shop.settings.receipt',['settings'=>ReceiptSetting::firstOrFail(),'shopLocations'=>Master::where('type','location')->where('is_active',true)->orderBy('name')->get()]);}
    public function update(Request $request)
    {
        $data=$request->validate(['company_name'=>'required|string|max:180','tagline'=>'nullable|string|max:180','logo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048','default_location_id'=>['nullable',Rule::exists('safety_shop_masters','id')->where('type','location')->where('is_active',true)],'vat_number'=>'nullable|string|max:30','commercial_registration'=>'nullable|string|max:30','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:150','website'=>'nullable|url|max:150','address'=>'nullable|string|max:500','city'=>'nullable|string|max:100','postal_code'=>'nullable|string|max:20','footer_text'=>'nullable|string|max:500']);
        $settings=ReceiptSetting::firstOrFail(); $oldLogo=$settings->logo_path; unset($data['logo']);
        if($request->hasFile('logo')) $data['logo_path']=$request->file('logo')->store('safety-shop-branding','local');
        elseif($request->boolean('remove_logo')) $data['logo_path']=null;
        $settings->update($data);
        if($oldLogo && !str_starts_with($oldLogo,'public:') && array_key_exists('logo_path',$data) && $oldLogo!==$data['logo_path']) Storage::disk('local')->delete($oldLogo);
        ActivityLog::record('Updated global document branding',$settings->company_name);
        return back()->with('success','Global document branding updated. New documents will use these details.');
    }
}
