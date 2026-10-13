<?php
namespace App\Modules\SafetyShop\Shared\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Modules\SafetyShop\Shared\Models\Master;
use Illuminate\Validation\Rule;
use App\Models\Company;
class ReceiptSettingController extends Controller
{
    public function edit(Request $request)
    {
        $company=$request->user()->company;
        $settings=ReceiptSetting::firstOrCreate(['company_id'=>$company->id],['company_name'=>$company->name,'tagline'=>'Workforce Management','footer_text'=>'Thank you for your business.']);
        $shopLocations=Master::where('company_id',$company->id)->where('type','location')->where('is_active',true)->orderBy('name')->get();
        return view('safety-shop.settings.receipt',compact('settings','shopLocations','company'));
    }
    public function update(Request $request)
    {
        $companyId=$request->user()->company_id;
        $data=$request->validate(['company_name'=>'required|string|max:180','company_name_ar'=>'nullable|string|max:180','tagline'=>'nullable|string|max:180','tagline_ar'=>'nullable|string|max:180','logo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048','header_image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120','footer_image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120','default_location_id'=>['nullable',Rule::exists('safety_shop_masters','id')->where('company_id',$companyId)->where('type','location')->where('is_active',true)],'vat_number'=>'nullable|string|max:30','commercial_registration'=>'nullable|string|max:30','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:150','website'=>'nullable|url|max:150','address'=>'nullable|string|max:500','address_ar'=>'nullable|string|max:500','city'=>'nullable|string|max:100','city_ar'=>'nullable|string|max:100','postal_code'=>'nullable|string|max:20','footer_text'=>'nullable|string|max:500']);
        $company=$request->user()->company;
        $settings=ReceiptSetting::forCompany($company->id)->firstOrFail();
        $oldFiles=['logo_path'=>$settings->logo_path,'header_image_path'=>$settings->header_image_path,'footer_image_path'=>$settings->footer_image_path];
        unset($data['logo'],$data['header_image'],$data['footer_image']);
        if($request->hasFile('logo')) $data['logo_path']=$request->file('logo')->store('company-branding/'.$companyId,'local');
        elseif($request->boolean('remove_logo')) $data['logo_path']=null;
        foreach(['header','footer'] as $section){
            $input=$section.'_image'; $column=$section.'_image_path';
            if($request->hasFile($input)) $data[$column]=$request->file($input)->store('company-branding/'.$companyId.'/letterhead','local');
            elseif($request->boolean('remove_'.$input)) $data[$column]=null;
        }
        $settings->update($data);
        foreach($oldFiles as $column=>$oldPath) if($oldPath && !str_starts_with($oldPath,'public:') && array_key_exists($column,$data) && $oldPath!==$data[$column]) Storage::disk('local')->delete($oldPath);
        ActivityLog::record('Updated company document branding',$settings->company_name);
        return back()->with('success','Company branding updated. New documents will use these details.');
    }
}
