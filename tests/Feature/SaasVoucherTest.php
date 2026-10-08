<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaasVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['saas.saas_voucher_enabled'=>true, 'saas.grace_days'=>7]);
    }

    private function user($role, $company=null)
    {
        $username=$role.uniqid();
        return User::create(['name'=>$role,'username'=>$username,'email'=>$username.'@test.local','password'=>Hash::make('Strong-password-123'),'role'=>$role,'company_id'=>optional($company)->id,'is_active'=>true]);
    }

    private function company($code)
    {
        return Company::create(['name'=>'Company '.$code,'company_code'=>$code,'location'=>'Riyadh','is_active'=>true,'subscription_status'=>'inactive']);
    }

    public function test_superadmin_issues_and_assigned_company_admin_redeems_once()
    {
        $super=$this->user('super_admin'); $company=$this->company('ONE'); $admin=$this->user('admin',$company);
        $this->actingAs($super)->post(route('saas.vouchers.store'),['duration_days'=>30,'assigned_company_id'=>$company->id,'valid_until'=>today()->addMonth()->format('Y-m-d')])->assertRedirect(route('saas.vouchers.index'));
        $code=session('new_voucher'); $this->assertNotEmpty($code);
        $this->assertDatabaseMissing('saas_vouchers',['code_hash'=>$code]);
        $this->flushSession();
        $this->actingAs($admin)->post(route('subscription.redeem'),['voucher'=>$code])->assertSessionHasNoErrors();
        $this->assertSame('ACTIVE',$company->fresh()->subscriptionState());
        $this->get(route('subscription.show'))->assertOk()->assertSee($company->fresh()->subscription_expires_at->format('d M Y'))->assertSee('Redemption history');
        $this->actingAs($admin)->post(route('subscription.redeem'),['voucher'=>$code])->assertSessionHasErrors('voucher');
    }

    public function test_cross_company_redemption_is_rejected_and_inactive_users_are_gated()
    {
        $super=$this->user('super_admin'); $one=$this->company('ONE'); $two=$this->company('TWO');
        $admin=$this->user('admin',$two); $manager=$this->user('manager',$two);
        $this->actingAs($super)->post(route('saas.vouchers.store'),['duration_days'=>30,'assigned_company_id'=>$one->id]);
        $code=session('new_voucher');
        $this->flushSession();
        $this->actingAs($admin)->post(route('subscription.redeem'),['voucher'=>$code])->assertSessionHasErrors('voucher');
        $this->flushSession();
        $this->actingAs($manager)->get(route('dashboard'))->assertRedirect(route('subscription.show'));
        $this->actingAs($manager)->post(route('subscription.redeem'),['voucher'=>$code])->assertForbidden();
    }

    public function test_superadmin_can_remove_company_access_and_unused_user()
    {
        $super=$this->user('super_admin'); $company=$this->company('REMOVE');
        $company->update(['subscription_status'=>'active','subscription_expires_at'=>today()->addMonth()]);
        $manager=$this->user('manager',$company);
        $this->actingAs($super)->put(route('saas.companies.status',$company),['subscription_status'=>'suspended'])->assertSessionHasNoErrors();
        $this->assertSame('SUSPENDED',$company->fresh()->subscriptionState());
        $this->flushSession();
        $this->actingAs($manager)->get(route('dashboard'))->assertRedirect(route('subscription.show'));
        $this->flushSession();
        $this->actingAs($super)->delete(route('users.destroy',$manager))->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users',['id'=>$manager->id]);
    }

    public function test_superadmin_cannot_remove_own_account()
    {
        $super=$this->user('super_admin');
        $this->actingAs($super)->delete(route('users.destroy',$super))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users',['id'=>$super->id]);
    }

    public function test_user_directory_groups_accounts_into_company_cards()
    {
        $super=$this->user('super_admin'); $alpha=$this->company('ALPHA'); $beta=$this->company('BETA');
        $this->user('admin',$alpha); $this->user('manager',$beta);
        $this->actingAs($super)->get(route('users.index'))->assertOk()
            ->assertSee('Platform superadmins')->assertSee('Company ALPHA')->assertSee('Company BETA')
            ->assertSee('Company Admin')->assertSee('Branch Manager');
    }

    public function test_document_branding_page_manages_each_company_name()
    {
        $super=$this->user('super_admin'); $company=$this->company('BRAND');
        $this->actingAs($super)->get(route('document-branding.edit'))->assertOk()->assertSee('Company BRAND')->assertSee('Company branding');
        $this->put(route('document-branding.companies.update',$company),['name'=>'Updated Client Name'])->assertSessionHasNoErrors();
        $this->assertSame('Updated Client Name',$company->fresh()->name);
    }

}
