<?php
namespace Tests\Feature;

use App\Models\User;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvoiceModuleTest extends TestCase
{
    use RefreshDatabase;
    private $admin; private $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin=User::create(['name'=>'Invoice Admin','username'=>'invoice_admin','email'=>'invoice_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'super_admin','is_active'=>true]);
        $this->manager=User::create(['name'=>'Invoice Manager','username'=>'invoice_manager','email'=>'invoice_manager@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'manager','is_active'=>true]);
    }

    private function login($user){return $this->withSession(['password_hash_web'=>$user->getAuthPassword()])->actingAs($user);}
    private function payload(){return ['document_type'=>'invoice','invoice_type'=>'standard','customer_id'=>InvoiceCustomer::where('customer_type','business')->firstOrFail()->id,'issue_date'=>now()->format('Y-m-d'),'notes'=>'Demo payment terms','lines'=>[['description'=>'Consulting service','quantity'=>'2.00','unit_code'=>'HUR','unit_price'=>'100.00','discount'=>'10.00','tax_category'=>'standard','tax_rate_units'=>1500]]];}

    public function test_invoice_module_seeds_masters_and_calculates_vat()
    {
        $this->assertDatabaseCount('invoice_customers',3); $this->assertDatabaseCount('invoice_items',4);
        $response=$this->login($this->manager)->post(route('invoicing.store'),$this->payload());
        $invoice=Invoice::firstOrFail(); $response->assertRedirect(route('invoicing.show',$invoice));
        $this->assertSame(20000,$invoice->subtotal_cents); $this->assertSame(1000,$invoice->discount_cents); $this->assertSame(2850,$invoice->tax_cents); $this->assertSame(21850,$invoice->total_cents);
        $this->assertSame('draft',$invoice->status); $this->assertSame('not_connected',$invoice->zatca_status);
    }

    public function test_only_approver_can_number_invoice_and_export_professional_documents()
    {
        $this->login($this->manager)->post(route('invoicing.store'),$this->payload()); $invoice=Invoice::firstOrFail();
        $this->post(route('invoicing.approve',$invoice))->assertForbidden();
        $this->get(route('invoicing.pdf',$invoice))->assertForbidden();
        $this->login($this->admin)->post(route('invoicing.approve',$invoice))->assertSessionHasNoErrors();
        $invoice->refresh(); $this->assertSame('approved',$invoice->status); $this->assertSame('not_connected',$invoice->zatca_status); $this->assertStringStartsWith('INV-',$invoice->invoice_number);
        $this->get(route('invoicing.pdf',$invoice))->assertOk()->assertHeader('Content-Type','application/pdf');
        $xml=$this->get(route('invoicing.xml',$invoice))->assertOk()->assertHeader('Content-Type','application/xml; charset=UTF-8')->getContent();
        $this->assertStringNotContainsString('DEMO ONLY', $xml); $this->assertStringContainsString($invoice->uuid,$xml);
    }

    public function test_credit_note_requires_reference_invoice()
    {
        $payload=$this->payload(); $payload['document_type']='credit_note';
        $this->login($this->manager)->post(route('invoicing.store'),$payload)->assertSessionHasErrors('reference_invoice_id');
        $this->assertDatabaseCount('invoices',0);
    }

    public function test_invoice_features_have_separate_searchable_directories()
    {
        $this->login($this->manager)->get(route('invoicing.customers.index',['search'=>'Eastern']))
            ->assertOk()->assertSee('Customer directory')->assertSee('Eastern Engineering');
        $this->get(route('invoicing.items.index',['tax'=>'standard']))
            ->assertOk()->assertSee('Products &amp; services',false)->assertSee('15% Standard');
        $this->get(route('invoicing.settings.index'))
            ->assertOk()->assertSee('Invoice settings')->assertSee('read-only for managers');
        $this->get(route('invoicing.masters'))->assertRedirect(route('invoicing.customers.index'));
    }

    public function test_zatca_is_a_separate_inactive_module_and_invoice_qr_remains_available()
    {
        $this->login($this->manager)->get(route('zatca.index'))
            ->assertOk()->assertSee('ZATCA integration')->assertSee('Integration not configured');
        $this->post(route('invoicing.store'),$this->payload());
        $invoice=Invoice::firstOrFail();
        $this->get(route('invoicing.show',$invoice))
            ->assertOk()->assertSee('Invoice QR code')->assertSee('Encoded invoice details');
    }

    public function test_approver_can_edit_customer_while_manager_is_view_only()
    {
        $customer=InvoiceCustomer::where('customer_type','business')->firstOrFail();
        $this->login($this->manager)->get(route('invoicing.customers.edit',$customer))->assertForbidden();
        $payload=['name'=>'Eastern Engineering Company','name_ar'=>'شركة الهندسة الشرقية','customer_type'=>'business','vat_number'=>'310000000000003','commercial_registration'=>'2050000001','email'=>'billing@eastern.example','phone'=>'+966 13 900 1000','address'=>'Second Industrial City','city'=>'Dammam','postal_code'=>'32241','country_code'=>'SA','is_active'=>'1'];
        $this->put(route('invoicing.customers.update',$customer),$payload)->assertForbidden();
        $this->login($this->admin)->get(route('invoicing.customers.edit',$customer))->assertOk()->assertSee('Edit customer');
        $this->put(route('invoicing.customers.update',$customer),$payload)->assertRedirect(route('invoicing.customers.index'));
        $this->assertDatabaseHas('invoice_customers',['id'=>$customer->id,'name'=>'Eastern Engineering Company','email'=>'billing@eastern.example']);
        $this->assertDatabaseHas('activity_logs',['action'=>'Updated invoice customer']);
    }

    public function test_only_super_admin_can_customize_full_invoice_design()
    {
        $payload=['design_primary_color'=>'#123456','design_text_color'=>'#222222','design_header_bg'=>'#eef5f7','design_border_color'=>'#889999','invoice_title'=>'COMMERCIAL TAX INVOICE','invoice_title_ar'=>'فاتورة ضريبية تجارية','design_density'=>'comfortable','design_header_layout'=>'blank','design_title_alignment'=>'left','design_logo_width'=>72,'design_font_size'=>8,'invoice_footer'=>'Custom company contact and payment footer','show_bank_details'=>'1','show_qr'=>'1','show_company_cr'=>'1','show_seller_details'=>'1','show_customer_details'=>'1','show_references'=>'1','show_amount_words'=>'1','show_notes'=>'1','show_footer_uuid'=>'1'];
        $this->login($this->manager)->get(route('invoicing.design.index'))->assertForbidden();
        $this->put(route('invoicing.design.update'),$payload)->assertForbidden();
        $this->login($this->admin)->get(route('invoicing.design.index'))->assertOk()->assertSee('Invoice design studio');
        $this->put(route('invoicing.design.update'),$payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoice_settings',['design_primary_color'=>'#123456','invoice_title'=>'COMMERCIAL TAX INVOICE','design_header_layout'=>'blank','design_logo_width'=>72,'show_signatures'=>0,'show_qr'=>1]);
        $this->assertDatabaseHas('activity_logs',['action'=>'Updated invoice design']);
    }
}
