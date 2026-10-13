<?php

namespace Tests\Feature;

use App\Models\CommercialDocument;
use App\Models\Company;
use App\Models\User;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $quote = ['customer_id' => $this->customer->id, 'document_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(), 'reference' => 'RFQ-01', 'lines' => [['product_id' => $this->product->id, 'description' => 'Safety Helmet', 'sku' => 'PPE-100', 'unit' => 'piece', 'quantity' => 2, 'unit_price' => '50.00'], ['description' => '']]];
        $this->login($this->issuer)->post(route('quotations.store'), $quote)->assertSessionHasNoErrors();
        $quotation = CommercialDocument::where('type', 'quotation')->firstOrFail();
        $this->assertSame('draft', $quotation->status);
        $this->assertSame(10000, $quotation->subtotal_cents);
        $this->post(route('quotations.approve', $quotation))->assertForbidden();
        $this->login($this->manager)->post(route('quotations.approve', $quotation))->assertSessionHasNoErrors();
        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertSame('QTN-'.str_pad($quotation->id, 6, '0', STR_PAD_LEFT), $quotation->number);
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

    public function test_each_module_has_an_independent_boolean_switch(): void
    {
        config(['safety_shop.enabled' => false]);
        $this->login($this->manager)->get(route('quotations.index'))->assertOk();
        $this->get(route('delivery-notes.index'))->assertOk();
        config(['commercial_documents.quotation_enabled' => false]);
        $this->get(route('quotations.index'))->assertNotFound();
        $this->get(route('delivery-notes.index'))->assertOk();
    }
}
