<?php

namespace Tests\Feature;

use App\Models\CommercialDocument;
use App\Models\Company;
use App\Models\User;
use App\Models\QuotationItemMaster;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Stock\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Modules\SafetyShop\Sales\Models\Sale;
use Tests\TestCase;

class CommercialDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $issuer;
    private User $manager;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        config(['safety_shop.enabled' => true, 'commercial_documents.quotation_enabled' => true, 'commercial_documents.delivery_note_enabled' => true]);
        $this->company = Company::create(['name' => 'Commercial Documents Co', 'location' => 'Jubail']);
        $this->issuer = User::create(['name' => 'Issuer', 'username' => 'docissuer', 'email' => 'issuer@docs.test', 'password' => Hash::make('Password-123'), 'role' => 'issuer', 'company_id' => $this->company->id, 'is_active' => true]);
        $this->manager = User::create(['name' => 'Manager', 'username' => 'docmanager', 'email' => 'manager@docs.test', 'password' => Hash::make('Password-123'), 'role' => 'manager', 'company_id' => $this->company->id, 'is_active' => true]);
        $this->customer = Customer::create(['company_id' => $this->company->id, 'customer_type' => 'company', 'name' => 'Jubail Bulk Buyer', 'vat_number' => '314716033700003', 'address' => 'Jubail Industrial City', 'country_code' => 'SA', 'approval_status' => 'approved', 'approved_by' => $this->manager->id, 'approved_at' => now(), 'is_active' => true]);
        $this->product = Product::create(['company_id' => $this->company->id, 'sku' => 'PPE-100', 'barcode' => '6290001000001', 'name' => 'Safety Helmet', 'unit' => 'piece', 'cost_cents' => 2000, 'price_cents' => 5000, 'reorder_level' => 1, 'is_active' => true]);
        ReceiptSetting::create(['company_id' => $this->company->id, 'company_name' => $this->company->name, 'vat_number' => '314482276100003']);
    }

    private function login(User $user)
    {
        return $this->withSession(['password_hash_web' => $user->getAuthPassword()])->actingAs($user);
    }

    public function test_issuer_creates_and_manager_approves_independent_documents(): void
    {
        $quote = ['customer_id' => $this->customer->id, 'document_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(), 'reference' => 'RFQ-01', 'discount_type'=>'percent','discount_value'=>'10.00', 'lines' => [['product_id' => $this->product->id, 'description' => 'Safety Helmet', 'sku' => 'PPE-100', 'unit' => 'piece', 'quantity' => 2, 'unit_price' => '50.00'], ['description' => '']]];
        $this->login($this->issuer)->post(route('quotations.store'), $quote)->assertSessionHasNoErrors();
        $quotation = CommercialDocument::where('type', 'quotation')->firstOrFail();
        $this->assertSame('draft', $quotation->status);
        $this->assertSame(10000, $quotation->subtotal_cents);
        $this->assertSame(1000, $quotation->discount_cents);
        $this->assertSame(1350, $quotation->tax_cents);
        $this->assertSame(10350, $quotation->total_cents);
        $this->post(route('quotations.approve', $quotation))->assertForbidden();
        $superAdmin=User::create(['name'=>'System Owner','username'=>'system_owner_quote_test','email'=>'system-owner-quote@test.local','password'=>Hash::make('Password-123'),'role'=>'super_admin','company_id'=>null,'is_active'=>true]);
        $this->login($superAdmin)->post(route('quotations.approve',$quotation))->assertForbidden();
        $this->login($this->manager)->post(route('quotations.approve', $quotation))->assertSessionHasNoErrors();
        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertMatchesRegularExpression('/^QTN-[A-Z0-9]{8}$/', $quotation->number);
        $this->assertNotSame('QTN-'.str_pad($quotation->id, 6, '0', STR_PAD_LEFT), $quotation->number);
        $this->get(route('quotations.pdf', $quotation))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $delivery = ['customer_id' => $this->customer->id, 'quotation_id' => $quotation->id, 'document_date' => now()->toDateString(), 'reference' => 'PO-88', 'lines' => [['product_id' => $this->product->id, 'description' => 'Safety Helmet', 'sku' => 'PPE-100', 'unit' => 'piece', 'quantity' => 2]]];
        $this->login($this->issuer)->post(route('delivery-notes.store'), $delivery)->assertSessionHasNoErrors();
        $note = CommercialDocument::where('type', 'delivery_note')->firstOrFail();
        $this->assertSame($quotation->id, $note->quotation_id);
        $this->login($this->manager)->post(route('delivery-notes.approve', $note))->assertSessionHasNoErrors();
        $this->assertStringStartsWith('DN-', $note->fresh()->number);
        $this->get(route('delivery-notes.pdf', $note))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_company_cannot_read_or_link_another_company_document(): void
    {
        $other = Company::create(['name' => 'Other Tenant', 'location' => 'Riyadh']);
        $otherManager = User::create(['name' => 'Other Manager', 'username' => 'otherdocmanager', 'email' => 'other@docs.test', 'password' => Hash::make('Password-123'), 'role' => 'manager', 'company_id' => $other->id, 'is_active' => true]);
        $foreignCustomer = Customer::create(['company_id' => $other->id, 'customer_type' => 'company', 'name' => 'Private Buyer', 'country_code' => 'SA', 'approval_status' => 'approved', 'approved_by' => $otherManager->id, 'approved_at' => now(), 'is_active' => true]);
        $foreign = CommercialDocument::create(['company_id' => $other->id, 'type' => 'quotation', 'customer_id' => $foreignCustomer->id, 'document_date' => now(), 'valid_until' => now()->addDay(), 'created_by' => $otherManager->id]);
        $this->login($this->manager)->get(route('quotations.show', $foreign))->assertNotFound();
        $this->login($this->issuer)->post(route('delivery-notes.store'), ['customer_id' => $this->customer->id, 'quotation_id' => $foreign->id, 'document_date' => now()->toDateString(), 'lines' => [['description' => 'Item', 'unit' => 'EA', 'quantity' => 1]]])->assertSessionHasErrors('quotation_id');
    }

    public function test_manual_quotation_items_become_reusable_master_data(): void
    {
        $payload=['customer_id'=>$this->customer->id,'document_date'=>now()->toDateString(),'valid_until'=>now()->addDays(14)->toDateString(),'lines'=>[['description'=>'Annual PPE inspection service','sku'=>'SVC-PPE-01','unit'=>'service','quantity'=>1,'unit_price'=>'275.00']]];
        $this->login($this->issuer)->post(route('quotations.store'),$payload)->assertSessionHasNoErrors();
        $master=QuotationItemMaster::where('company_id',$this->company->id)->where('sku','SVC-PPE-01')->firstOrFail();
        $this->assertSame(27500,$master->unit_price_cents);
        $this->assertDatabaseHas('commercial_document_lines',['quotation_item_master_id'=>$master->id,'description'=>'Annual PPE inspection service']);
        $this->login($this->issuer)->get(route('quotations.create'))->assertOk()->assertSee('Annual PPE inspection service');
    }

    public function test_repeated_quotation_product_is_saved_once_with_combined_quantity(): void
    {
        $payload=['customer_id'=>$this->customer->id,'document_date'=>now()->toDateString(),'valid_until'=>now()->addDays(14)->toDateString(),'lines'=>[
            ['product_id'=>$this->product->id,'description'=>'Safety Helmet','sku'=>$this->product->sku,'unit'=>'piece','quantity'=>2,'unit_price'=>'50.00'],
            ['product_id'=>$this->product->id,'description'=>'Safety Helmet','sku'=>$this->product->sku,'unit'=>'piece','quantity'=>3,'unit_price'=>'50.00'],
        ]];
        $this->login($this->issuer)->post(route('quotations.store'),$payload)->assertSessionHasNoErrors();
        $quotation=CommercialDocument::with('lines')->firstOrFail();
        $this->assertCount(1,$quotation->lines);
        $this->assertSame(5.0,(float)$quotation->lines->first()->quantity);
        $this->assertSame(25000,$quotation->subtotal_cents);
    }

    public function test_approved_quotation_can_prefill_invoice_checkout(): void
    {
        $location=Master::create(['company_id'=>$this->company->id,'type'=>'location','name'=>'Main Shop','is_active'=>true]);
        Stock::create(['product_id'=>$this->product->id,'location_id'=>$location->id,'quantity'=>12]);
        $quotation=CommercialDocument::create(['company_id'=>$this->company->id,'type'=>'quotation','number'=>'QTN-AB12CD34','customer_id'=>$this->customer->id,'document_date'=>now(),'valid_until'=>now()->addMonth(),'reference'=>'PO-55','notes'=>'Deliver to main warehouse.','subtotal_cents'=>9000,'discount_type'=>'fixed','discount_value'=>500,'discount_cents'=>500,'tax_cents'=>1275,'total_cents'=>9775,'status'=>'approved','created_by'=>$this->issuer->id,'approved_by'=>$this->manager->id,'approved_at'=>now()]);
        $quotation->lines()->create(['product_id'=>$this->product->id,'description'=>'Quoted Safety Helmet','sku'=>$this->product->sku,'unit'=>'piece','quantity'=>2,'unit_price_cents'=>4500,'subtotal_cents'=>9000,'tax_rate_units'=>1500,'tax_cents'=>1350]);
        $quotation->lines()->create(['product_id'=>$this->product->id,'description'=>'Quoted Safety Helmet','sku'=>$this->product->sku,'unit'=>'piece','quantity'=>1,'unit_price_cents'=>4500,'subtotal_cents'=>4500,'tax_rate_units'=>1500,'tax_cents'=>675]);
        $this->login($this->manager)->get(route('safety-shop.sales.create'))->assertOk()->assertSee('Invoice from approved quotation')->assertSee('QTN-AB12CD34');
        $this->getJson(route('safety-shop.sales.quotation',[$quotation,'location_id'=>$location->id]))->assertOk()->assertJson(['number'=>'QTN-AB12CD34','customer_id'=>$this->customer->id,'customer_po_number'=>'PO-55','discount'=>'5.00','lines'=>[['id'=>$this->product->id,'price_cents'=>4500,'available'=>12,'quantity'=>3]]]);
        $this->post(route('safety-shop.sales.store'),['request_key'=>(string)Str::uuid(),'quotation_id'=>$quotation->id,'customer_po_number'=>'PO-55','location_id'=>$location->id,'supply_date'=>now()->toDateString(),'customer_id'=>$this->customer->id,'customer_type'=>'company','discount'=>'5.00','tax_rate'=>'15.00','cash_paid'=>'0.00','card_paid'=>'0.00','bank_paid'=>'0.00','lines'=>[['product_id'=>$this->product->id,'quantity'=>2,'price_cents'=>4500]]])->assertSessionHasNoErrors();
        $sale=Sale::firstOrFail();
        $this->assertSame($quotation->id,(int)$sale->quotation_id);
        $this->assertSame('PO-55',$sale->customer_po_number);
        $this->assertSame(500,$sale->discount_cents);
        $this->assertSame(4500,$sale->lines()->firstOrFail()->price_cents);
    }

    public function test_quotation_invoice_is_blocked_when_location_stock_is_insufficient(): void
    {
        $location=Master::create(['company_id'=>$this->company->id,'type'=>'location','name'=>'Low Stock Shop','is_active'=>true]);
        Stock::create(['product_id'=>$this->product->id,'location_id'=>$location->id,'quantity'=>1]);
        $quotation=CommercialDocument::create(['company_id'=>$this->company->id,'type'=>'quotation','number'=>'QTN-NOSTOCK','customer_id'=>$this->customer->id,'document_date'=>now(),'valid_until'=>now()->addMonth(),'subtotal_cents'=>10000,'tax_cents'=>1500,'total_cents'=>11500,'status'=>'approved','created_by'=>$this->issuer->id,'approved_by'=>$this->manager->id,'approved_at'=>now()]);
        $quotation->lines()->create(['product_id'=>$this->product->id,'description'=>'Safety Helmet','sku'=>$this->product->sku,'unit'=>'piece','quantity'=>2,'unit_price_cents'=>5000,'subtotal_cents'=>10000,'tax_rate_units'=>1500,'tax_cents'=>1500]);

        $this->login($this->manager)->getJson(route('safety-shop.sales.quotation',[$quotation,'location_id'=>$location->id]))
            ->assertStatus(422)->assertJsonFragment(['message'=>'Invoice cannot be generated because quoted stock is unavailable at this location: Safety Helmet (required 2, available 1).']);

        $this->post(route('safety-shop.sales.store'),['request_key'=>(string)Str::uuid(),'quotation_id'=>$quotation->id,'location_id'=>$location->id,'supply_date'=>now()->toDateString(),'customer_id'=>$this->customer->id,'customer_type'=>'company','discount'=>'0.00','tax_rate'=>'15.00','cash_paid'=>'0.00','card_paid'=>'0.00','bank_paid'=>'0.00','lines'=>[['product_id'=>$this->product->id,'quantity'=>2,'price_cents'=>5000]]])
            ->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('safety_shop_sales',['quotation_id'=>$quotation->id]);
        $this->assertSame(1,(int)Stock::where('location_id',$location->id)->where('product_id',$this->product->id)->value('quantity'));
    }

    public function test_admin_edits_drafts_and_revises_approved_quotations_without_overwriting_history(): void
    {
        $admin=User::create(['name'=>'Company Admin','username'=>'quotation_admin','email'=>'quotation-admin@docs.test','password'=>Hash::make('Password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $payload=['customer_id'=>$this->customer->id,'document_date'=>now()->toDateString(),'valid_until'=>now()->addDays(30)->toDateString(),'reference'=>'RFQ-REV','notes'=>'Original terms','lines'=>[['product_id'=>$this->product->id,'description'=>'Safety Helmet','sku'=>$this->product->sku,'unit'=>'piece','quantity'=>1,'unit_price'=>'50.00']]];
        $this->login($this->issuer)->post(route('quotations.store'),$payload)->assertSessionHasNoErrors();$original=CommercialDocument::firstOrFail();
        $this->login($admin)->get(route('quotations.edit',$original))->assertOk()->assertSee('Edit Quotation');
        $payload['lines'][0]['quantity']=2;$this->put(route('quotations.update',$original),$payload)->assertSessionHasNoErrors();$this->assertSame(2.0,(float)$original->fresh()->lines()->first()->quantity);
        $this->post(route('quotations.approve',$original))->assertSessionHasNoErrors();$original->refresh();$originalNumber=$original->number;
        $this->get(route('quotations.edit',$original))->assertNotFound();
        $this->post(route('quotations.revise',$original))->assertRedirect();$revision=CommercialDocument::where('revision_of_id',$original->id)->firstOrFail();$this->assertSame('draft',$revision->status);$this->assertSame(1,(int)$revision->revision_number);
        $payload['notes']='Revised commercial terms';$payload['lines'][0]['quantity']=3;$this->put(route('quotations.update',$revision),$payload)->assertSessionHasNoErrors();$this->post(route('quotations.approve',$revision))->assertSessionHasNoErrors();
        $this->assertSame($originalNumber.'-R1',$revision->fresh()->number);$this->assertSame($revision->id,(int)$original->fresh()->superseded_by_id);$this->assertSame('superseded',$original->fresh()->lifecycle_status);$this->assertSame(2.0,(float)$original->lines()->first()->quantity);$this->assertSame(3.0,(float)$revision->lines()->first()->quantity);
        $this->get(route('quotations.show',$original))->assertOk()->assertSee('superseded by')->assertSee($revision->number);

        $this->login($this->issuer)->post(route('quotations.store'),$payload)->assertSessionHasNoErrors();$cancelled=CommercialDocument::where('status','draft')->latest('id')->firstOrFail();
        $this->login($admin)->post(route('quotations.cancel',$cancelled),['cancellation_reason'=>'Customer withdrew the request.'])->assertSessionHasNoErrors();$this->assertDatabaseHas('commercial_documents',['id'=>$cancelled->id,'status'=>'cancelled','cancelled_by'=>$admin->id,'cancellation_reason'=>'Customer withdrew the request.']);
    }

    public function test_each_module_has_an_independent_boolean_switch(): void
    {
        config(['safety_shop.enabled' => false]);
        $this->login($this->manager)->get(route('quotations.index'))->assertNotFound();
        $this->get(route('delivery-notes.index'))->assertNotFound();
        config(['safety_shop.enabled' => true]);
        config(['commercial_documents.quotation_enabled' => false]);
        $this->get(route('quotations.index'))->assertNotFound();
        $this->get(route('delivery-notes.index'))->assertOk();
    }
}
