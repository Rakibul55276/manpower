<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Company;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Reports\Services\FinancialReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;
class SafetyShopTest extends TestCase
{
    use RefreshDatabase;
    private $admin; private $manager; private $company; private $product; private $location; private $destination; private $supplier;
    protected function setUp(): void
    {
        parent::setUp();
        config(['safety_shop.enabled'=>true,'invoicing.enabled'=>false,'zatca.enabled'=>false]);
        $this->admin=User::create(['name'=>'Shop Admin','username'=>'shopadmin','email'=>'shopadmin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'super_admin','is_active'=>true]);
        $this->manager=User::create(['name'=>'Shop Reader','username'=>'shopreader','email'=>'shopreader@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'manager','is_active'=>true]);
        $this->company=Company::create(['name'=>'Safety Shop Test Company','location'=>'Riyadh']);
        $this->location=Master::create(['company_id'=>$this->company->id,'type'=>'location','name'=>'Shop counter']);
        $this->destination=Master::create(['company_id'=>$this->company->id,'type'=>'location','name'=>'Warehouse']);
        $this->supplier=Master::create(['company_id'=>$this->company->id,'type'=>'supplier','name'=>'PPE Supplier']);
        $this->product=Product::create(['company_id'=>$this->company->id,'sku'=>'HELMET-01','barcode'=>'001234567890','name'=>'Safety helmet','unit'=>'piece','cost_cents'=>1000,'price_cents'=>2500,'reorder_level'=>3]);
    }
    private function login($user=null) { $user=$user??$this->admin; return $this->withSession(['password_hash_web'=>$user->getAuthPassword()])->actingAs($user); }
    private function movement($type='receipt',$quantity=10,$extra=[])
    {
        return array_merge(['company_id'=>$this->company->id,'request_key'=>(string)Str::uuid(),'type'=>$type,'product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>$quantity,'movement_date'=>now()->format('Y-m-d'),'notes'=>'Test stock movement','recipient'=>'Walk-in customer'],$extra);
    }
    private function sale($quantity=2,$extra=[])
    {
        return array_merge(['request_key'=>(string)Str::uuid(),'location_id'=>$this->location->id,'customer'=>'Counter customer','customer_phone'=>'+966 55 123 4567','payment_method'=>'cash','paid'=>'60.00','lines'=>[['product_id'=>$this->product->id,'quantity'=>$quantity,'price_cents'=>2500]]],$extra);
    }
    private function receive($quantity=10) { $this->login()->post(route('safety-shop.stock.store'),$this->movement('receipt',$quantity))->assertSessionHasNoErrors(); }
    public function test_stock_form_can_select_an_existing_product_by_barcode()
    {
        $this->login()->get(route('safety-shop.stock.create'))
            ->assertOk()
            ->assertSee('Product barcode')
            ->assertSee('data-barcode="001234567890"',false)
            ->assertSee('id="destination-field"',false)
            ->assertSee('js/safety-shop/stock/form.js');
    }
    public function test_company_admin_can_manage_only_its_company_stock()
    {
        $companyAdmin=User::create(['name'=>'Stock Company Admin','username'=>'stock_company_admin','email'=>'stock_company_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $other=Company::create(['name'=>'Private Stock Company','location'=>'Jeddah']);
        $privateProduct=Product::create(['company_id'=>$other->id,'sku'=>'PRIVATE-001','barcode'=>'999000111222','name'=>'Private product','unit'=>'piece','cost_cents'=>100,'price_cents'=>200,'reorder_level'=>1]);
        Master::create(['company_id'=>$other->id,'type'=>'location','name'=>'Private warehouse']);
        $this->login($companyAdmin)->get(route('safety-shop.stock.create'))->assertOk()->assertSee($this->product->name)->assertDontSee($privateProduct->name);
        $this->get(route('safety-shop.stock.index'))->assertOk()->assertDontSee($privateProduct->sku);
        $invalid=$this->movement('receipt',1,['company_id'=>$other->id,'product_id'=>$privateProduct->id]);
        $this->post(route('safety-shop.stock.store'),$invalid)->assertSessionHasErrors(['company_id','product_id']);
        $this->get(route('document-branding.edit'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Full analytics & audit')->assertDontSee('Company inventory overview')->assertSee('Safety Shop analytics')->assertSee('Net revenue excl. VAT')->assertSee('Inventory value')->assertSee('Monthly movements');
        $this->login($this->manager)->get(route('dashboard'))->assertOk()->assertDontSee('Safety Shop analytics')->assertDontSee('Net revenue excl. VAT')->assertDontSee('Combined monthly revenue');
    }
    public function test_stock_receipt_can_store_and_download_private_supporting_document()
    {
        $storageRoot=sys_get_temp_dir().DIRECTORY_SEPARATOR.'hr-safety-shop-test-storage';
        File::ensureDirectoryExists($storageRoot);
        config(['filesystems.disks.local.root'=>$storageRoot]);
        config(['private_documents.disk'=>'local','private_documents.directories.stock_movements'=>'custom/stock-receipts']);
        $payload=$this->movement('receipt',5);
        $payload['supporting_document']=UploadedFile::fake()->create('supplier-receipt.pdf',120,'application/pdf');
        $this->login()->post(route('safety-shop.stock.store'),$payload)->assertSessionHasNoErrors()->assertRedirect(route('safety-shop.stock.index'));
        $movement=Movement::latest('id')->firstOrFail();
        $this->assertSame('supplier-receipt.pdf',$movement->attachment_name);
        $this->assertStringStartsWith('custom/stock-receipts/',$movement->attachment_path);
        Storage::disk('local')->assertExists($movement->attachment_path);
        $this->get(route('safety-shop.stock.attachment',$movement))->assertOk();
        Storage::disk('local')->delete($movement->attachment_path);
    }
    public function test_supplier_accepts_optional_company_details()
    {
        $this->login()->post(route('safety-shop.suppliers.store'),[
            'name'=>'Industrial PPE Trading','contact_person'=>'Ahmed Ali','phone'=>'+966 55 000 0000',
            'email'=>'sales@example.test','vat_number'=>'310000000000003','commercial_registration'=>'1010000000',
            'website'=>'https://example.test','address'=>'Riyadh','is_active'=>1,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_masters',['company_id'=>$this->company->id,'type'=>'supplier','name'=>'Industrial PPE Trading','contact_person'=>'Ahmed Ali','vat_number'=>'310000000000003']);
    }
    public function test_product_form_dropdowns_include_saved_master_values()
    {
        foreach(['brand'=>'Ansell','size'=>'XL','unit'=>'box','safety_standard'=>'EN 397','category'=>'Head protection'] as $type=>$name) Master::create(['company_id'=>$this->company->id,'type'=>$type,'name'=>$name,'is_active'=>true]);
        $other=Company::create(['name'=>'Other Catalog Company','location'=>'Jeddah']);
        Master::create(['company_id'=>$other->id,'type'=>'brand','name'=>'PRIVATE-OTHER-COMPANY','is_active'=>true]);
        $response=$this->login()->get(route('safety-shop.products.create',['company_id'=>$this->company->id]))->assertOk();
        foreach(['Ansell','XL','box','EN 397','Head protection'] as $value)$response->assertSee($value);
        $response->assertDontSee('PRIVATE-OTHER-COMPANY')->assertSee('data-searchable-list',false)->assertSee('css/safety-shop/products/form.css');
    }
    public function test_sku_suggestion_uses_company_scoped_category_prefix()
    {
        $category=Master::create(['company_id'=>$this->company->id,'type'=>'category','name'=>'Head Protection','sku_prefix'=>'SAFE','is_active'=>true]);
        Product::create(['company_id'=>$this->company->id,'sku'=>'SAFE-0001','name'=>'Existing helmet','unit'=>'piece','cost_cents'=>100,'price_cents'=>200,'reorder_level'=>1]);
        $other=Company::create(['name'=>'Other SKU Company','location'=>'Jeddah']);
        Product::create(['company_id'=>$other->id,'sku'=>'SAFE-0099','name'=>'Other helmet','unit'=>'piece','cost_cents'=>100,'price_cents'=>200,'reorder_level'=>1]);
        $this->login()->getJson(route('safety-shop.products.sku-suggestion',['company_id'=>$this->company->id,'category_id'=>$category->id]))->assertOk()->assertJson(['sku'=>'SAFE-0002','prefix'=>'SAFE']);
        $this->getJson(route('safety-shop.products.sku-suggestion',['company_id'=>$other->id,'category_id'=>$category->id]))->assertNotFound();
    }
    public function test_product_sku_and_barcode_are_unique_within_company_only()
    {
        $other=Company::create(['name'=>'Independent Catalog Company','location'=>'Jeddah']);
        $payload=['company_id'=>$other->id,'sku'=>$this->product->sku,'barcode'=>$this->product->barcode,'name'=>'Other company helmet','unit'=>'piece','reorder_level'=>0,'cost'=>'10.00','price'=>'20.00','is_active'=>1];
        $this->login()->post(route('safety-shop.products.store'),$payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_products',['company_id'=>$other->id,'sku'=>$this->product->sku,'barcode'=>$this->product->barcode]);
        $payload['name']='Duplicate in same company';
        $this->post(route('safety-shop.products.store'),$payload)->assertSessionHasErrors(['sku','barcode']);
    }
    public function test_receipt_issue_return_and_adjustment_keep_ledger_and_balances()
    {
        $this->receive();
        foreach ([['issue',4],['return',2],['adjustment',-1]] as $step) { $payload=$this->movement($step[0],$step[1]); if($step[0]==='issue')unset($payload['recipient']); $this->post(route('safety-shop.stock.store'),$payload)->assertSessionHasNoErrors(); }
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>7]);
        $this->assertDatabaseCount('safety_shop_movements',4);
        $this->assertSame(-4,Movement::where('type','issue')->first()->quantity);
        $this->get(route('safety-shop.products.show',$this->product))->assertOk()->assertSee('Stock by location')->assertSee('001234567890');
        $this->assertDatabaseCount('invoices',0); $this->assertDatabaseCount('payrolls',0);
    }
    public function test_transfer_is_atomic_and_insufficient_stock_is_rejected()
    {
        $this->receive(5);
        $this->post(route('safety-shop.stock.store'),$this->movement('transfer',3,['destination_id'=>$this->destination->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_stocks',['location_id'=>$this->location->id,'quantity'=>2]);
        $this->assertDatabaseHas('safety_shop_stocks',['location_id'=>$this->destination->id,'quantity'=>3]);
        $this->post(route('safety-shop.stock.store'),$this->movement('transfer',9,['destination_id'=>$this->destination->id]))->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('safety_shop_movements',2);
        $this->assertSame(5,(int)Stock::sum('quantity'));
    }
    public function test_invalid_masters_inactive_products_negative_receipts_and_duplicate_posts_are_rejected()
    {
        $payload=$this->movement(); $this->login()->post(route('safety-shop.stock.store'),$payload)->assertSessionHasNoErrors();
        $this->post(route('safety-shop.stock.store'),$payload)->assertSessionHasErrors('request_key');
        $this->post(route('safety-shop.stock.store'),$this->movement('receipt',-2))->assertSessionHasErrors('quantity');
        $this->post(route('safety-shop.stock.store'),$this->movement('receipt',2,['location_id'=>$this->supplier->id]))->assertSessionHasErrors('location_id');
        $this->post(route('safety-shop.stock.store'),$this->movement('transfer',2,['destination_id'=>$this->location->id]))->assertSessionHasErrors('destination_id');
        $this->product->update(['is_active'=>false]);
        $this->post(route('safety-shop.stock.store'),$this->movement())->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('safety_shop_movements',1); $this->assertSame(10,(int)Stock::sum('quantity'));
    }
    public function test_roles_and_feature_flag_keep_module_independent()
    {
        $this->login($this->manager)->get(route('safety-shop.index'))->assertForbidden();
        $this->post(route('safety-shop.stock.store'),$this->movement())->assertForbidden();
        $this->get(route('safety-shop.sales.create'))->assertForbidden();
        $this->get(route('safety-shop.returns.index'))->assertForbidden();
        $this->get(route('safety-shop.returns.create'))->assertForbidden();
        $this->get(route('safety-shop.customers.index'))->assertForbidden();
        $this->get(route('safety-shop.customers.create'))->assertForbidden();
        $this->post(route('safety-shop.sales.store'),$this->sale())->assertForbidden();
        $this->get(route('safety-shop.products.create'))->assertForbidden();
        $this->get(route('safety-shop.categories.index'))->assertForbidden();
        $this->login()->get(route('safety-shop.index'))->assertOk()->assertDontSee('Invoice system')->assertDontSee('ZATCA integration');
        config(['safety_shop.enabled'=>false]);
        $this->get(route('safety-shop.index'))->assertNotFound();
        $this->post(route('safety-shop.stock.store'),$this->movement())->assertNotFound();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Stock overview')->assertSee('Monthly salaries');
    }
    public function test_product_barcode_and_prices_can_be_saved_and_barcode_is_unique()
    {
        $payload=['company_id'=>$this->company->id,'sku'=>'GLOVE-01','barcode'=>'000001','name'=>'Safety gloves','unit'=>'pair','cost'=>'3.07','price'=>'8.90','reorder_level'=>2,'is_active'=>1];
        $this->login()->post(route('safety-shop.products.store'),$payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_products',['barcode'=>'000001','cost_cents'=>307,'price_cents'=>890]);
        $payload['sku']='GLOVE-02';
        $this->post(route('safety-shop.products.store'),$payload)->assertSessionHasErrors('barcode');
        $this->get(route('safety-shop.products.edit',$this->product))->assertOk()->assertSee('Selling price')->assertSee('Barcode');
        $this->get(route('safety-shop.suppliers.index'))->assertOk()->assertSee('PPE Supplier');
    }
    public function test_barcode_sale_uses_saved_price_and_printable_snapshot_receipt()
    {
        $this->receive();
        $this->getJson(route('safety-shop.barcode',['barcode'=>'001234567890','location_id'=>$this->location->id]))->assertOk()->assertJson(['id'=>$this->product->id,'price_cents'=>2500,'available'=>10]);
        $payload=$this->sale();
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasNoErrors();
        $sale=Sale::firstOrFail(); $this->assertSame(5000,$sale->total_cents); $this->assertSame(6000,$sale->paid_cents); $this->assertSame('+966 55 123 4567',$sale->customer_phone);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'quantity'=>8]);
        $this->assertDatabaseHas('safety_shop_movements',['reference'=>'SALE-'.$sale->id,'quantity'=>-2]);
        $this->product->update(['name'=>'Updated helmet','price_cents'=>3500]);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('Safety helmet')->assertSee('Print receipt')->assertSee('+966 55 123 4567')->assertSee('10.00');
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('safety_shop_sales',1);
        $this->assertDatabaseCount('invoices',0);
    }
    public function test_failed_multiline_sale_rolls_back_all_stock_and_sales()
    {
        $this->receive();
        $second=Product::create(['company_id'=>$this->company->id,'sku'=>'EMPTY','barcode'=>'EMPTY','name'=>'Empty product','unit'=>'piece','price_cents'=>100]);
        $payload=$this->sale(2,['paid'=>'100.00']); $payload['lines'][]=['product_id'=>$second->id,'quantity'=>1,'price_cents'=>100];
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('safety_shop_sales',0); $this->assertDatabaseCount('safety_shop_sale_lines',0);
        $this->assertDatabaseCount('safety_shop_movements',1); $this->assertSame(10,(int)Stock::sum('quantity'));
        $this->assertDatabaseMissing('safety_shop_stocks',['product_id'=>$second->id]);
    }
    public function test_sale_rejects_underpayment_price_tampering_and_duplicate_lines()
    {
        $this->receive();
        $this->post(route('safety-shop.sales.store'),$this->sale(2,['paid'=>'1.00']))->assertSessionHasErrors('paid');
        $payload=$this->sale(); $payload['lines'][0]['price_cents']=1;
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('lines');
        $payload=$this->sale(); $payload['lines'][]=$payload['lines'][0];
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('lines.0.product_id');
        $this->post(route('safety-shop.sales.store'),$this->sale(2,['payment_method'=>'card']))->assertSessionHasErrors('paid');
        $this->assertDatabaseCount('safety_shop_sales',0); $this->assertSame(10,(int)Stock::sum('quantity'));
    }
    public function test_checkout_saves_and_reuses_customer_details()
    {
        $this->receive();
        $first=$this->sale(2,['customer'=>'Eastern Engineering','customer_phone'=>'+966 55 123 4567','customer_email'=>'buyer@example.com','customer_address'=>'Industrial Area, Riyadh']);
        $this->post(route('safety-shop.sales.store'),$first)->assertSessionHasNoErrors();
        $customer=Customer::firstOrFail();
        $this->assertSame('Eastern Engineering',$customer->name);
        $this->assertSame('+966551234567',$customer->phone);
        $this->assertSame(1,$customer->purchase_count);
        $this->assertSame(5000,$customer->lifetime_value_cents);
        $this->assertDatabaseHas('safety_shop_sales',['customer_id'=>$customer->id,'customer_email'=>'buyer@example.com','customer_address'=>'Industrial Area, Riyadh']);

        $second=$this->sale(1,['customer_id'=>$customer->id,'customer'=>'Eastern Engineering','customer_phone'=>'+966551234567','customer_email'=>'accounts@example.com','customer_address'=>'Updated Riyadh address']);
        $this->post(route('safety-shop.sales.store'),$second)->assertSessionHasNoErrors();
        $customer->refresh();
        $this->assertSame(2,$customer->purchase_count);
        $this->assertSame(7500,$customer->lifetime_value_cents);
        $this->assertSame('accounts@example.com',$customer->email);
        $this->assertDatabaseCount('safety_shop_customers',1);
        $this->get(route('safety-shop.sales.create'))->assertOk()->assertSee('Saved customer')->assertSee('Eastern Engineering')->assertSee('accounts@example.com');
    }
    public function test_discount_vat_and_split_payments_are_calculated_and_printed()
    {
        $this->receive();
        $payload=$this->sale(2,[
            'discount'=>'10.00','tax_rate'=>'15.00','cash_paid'=>'20.00','card_paid'=>'26.00','bank_paid'=>'0.00',
        ]);
        unset($payload['payment_method'],$payload['paid']);
        $this->login()->post(route('safety-shop.sales.store'),$payload)->assertSessionHasNoErrors();
        $sale=Sale::firstOrFail();
        $this->assertSame(5000,$sale->subtotal_cents); $this->assertSame(1000,$sale->discount_cents);
        $this->assertSame(4000,$sale->taxable_cents); $this->assertSame(1500,$sale->tax_rate_units);
        $this->assertSame(600,$sale->tax_cents); $this->assertSame(4600,$sale->total_cents);
        $this->assertSame(2000,$sale->cash_cents); $this->assertSame(2600,$sale->card_cents);
        $this->assertSame('split',$sale->payment_method);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('VAT (15.00%)')->assertSee('Cash')->assertSee('SAR 20.00')->assertSee('Card')->assertSee('SAR 26.00');

        $invalid=$this->sale(1,['discount'=>'30.00','tax_rate'=>'15','cash_paid'=>'0','card_paid'=>'0','bank_paid'=>'0']);
        unset($invalid['payment_method'],$invalid['paid']);
        $this->post(route('safety-shop.sales.store'),$invalid)->assertSessionHasErrors('discount');
    }
    public function test_receipt_linked_return_restores_stock_and_prevents_over_return()
    {
        $this->receive();
        $this->post(route('safety-shop.sales.store'),$this->sale(2))->assertSessionHasNoErrors();
        $sale=Sale::with('lines')->firstOrFail();

        $this->getJson(route('safety-shop.returns.lookup',['receipt'=>'SALE-'.$sale->id]))
            ->assertOk()->assertJson(['customer'=>'Counter customer','lines'=>[['returnable'=>2]]]);

        $payload=['request_key'=>(string)Str::uuid(),'sale_id'=>$sale->id,'refund_method'=>'cash','reason'=>'Unused item in original condition','lines'=>[['sale_line_id'=>$sale->lines->first()->id,'quantity'=>1,'barcode'=>'001234567890']]];
        $this->post(route('safety-shop.returns.store'),$payload)->assertSessionHasNoErrors();
        $return=SaleReturn::firstOrFail();
        $this->assertSame(2500,$return->refund_cents);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>9]);
        $this->assertDatabaseHas('safety_shop_movements',['reference'=>'RETURN-'.$return->id.' / SALE-'.$sale->id,'quantity'=>1]);
        $this->get(route('safety-shop.returns.show',$return))->assertOk()->assertSee('Counter customer')->assertSee('Unused item in original condition');

        $payload['request_key']=(string)Str::uuid(); $payload['lines'][0]['quantity']=2;
        $this->post(route('safety-shop.returns.store'),$payload)->assertSessionHasErrors('lines');
        $payload['request_key']=(string)Str::uuid(); $payload['lines'][0]['quantity']=1; $payload['lines'][0]['barcode']='WRONG';
        $this->post(route('safety-shop.returns.store'),$payload)->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('safety_shop_returns',1);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'quantity'=>9]);
    }
    public function test_financial_analytics_reconcile_sales_returns_cost_and_profit()
    {
        $this->receive();
        $salePayload=$this->sale(2,['discount'=>'10.00','tax_rate'=>'15.00','cash_paid'=>'46.00','card_paid'=>'0.00','bank_paid'=>'0.00']);
        unset($salePayload['payment_method'],$salePayload['paid']);
        $this->post(route('safety-shop.sales.store'),$salePayload)->assertSessionHasNoErrors();
        $sale=Sale::with('lines')->firstOrFail();
        $this->post(route('safety-shop.returns.store'),['request_key'=>(string)Str::uuid(),'sale_id'=>$sale->id,'refund_method'=>'cash','reason'=>'Audit reconciliation return','lines'=>[['sale_line_id'=>$sale->lines->first()->id,'quantity'=>1,'barcode'=>'001234567890']]])->assertSessionHasNoErrors();

        $report=app(FinancialReport::class)->build(['from'=>now()->toDateString(),'to'=>now()->toDateString()]);
        $this->assertSame(5000,$report['summary']['gross_sales']);
        $this->assertSame(2300,$report['summary']['refunds']);
        $this->assertSame(2000,$report['summary']['net_revenue']);
        $this->assertSame(1000,$report['summary']['net_cogs']);
        $this->assertSame(1000,$report['summary']['gross_profit']);
        $this->assertSame(300,$report['summary']['vat_collected']);
        $this->get(route('safety-shop.reports.index'))->assertOk()->assertSee('Financial audit trail')->assertSee('Gross profit / loss');
        $this->get(route('dashboard',['month'=>now()->format('Y-m')]))->assertOk()->assertSee('Safety Shop summary')->assertSee('Gross revenue excl. VAT')->assertSee('Net revenue excl. VAT')->assertSee('Inventory value');
        $csv=$this->get(route('safety-shop.reports.financial.csv'))->assertOk();
        $this->assertStringContainsString('SALE-'.$sale->id,$csv->streamedContent());
        $this->assertStringContainsString('RETURN-'.SaleReturn::firstOrFail()->id,$csv->streamedContent());
        $this->get(route('safety-shop.reports.financial.pdf'))->assertOk()->assertHeader('Content-Type','application/pdf');
    }
    public function test_feature_directories_have_separate_pages_and_master_types()
    {
        $this->login();
        foreach (['products.index','categories.index','categories.create','suppliers.index','suppliers.create','locations.index','locations.create','customers.index','customers.create','stock.index','stock.create','sales.index','sales.create','returns.index','returns.create','reports.index','products.create'] as $page) $this->get(route('safety-shop.'.$page))->assertOk();
        $this->post(route('safety-shop.suppliers.store'),['type'=>'category','name'=>'Separate supplier','is_active'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_masters',['type'=>'supplier','name'=>'Separate supplier']);
        $this->get(route('safety-shop.categories.index'))->assertDontSee('Separate supplier')->assertDontSee('Save category');
        $this->put(route('safety-shop.categories.update',$this->supplier),['name'=>'Wrong category','sku_prefix'=>'WRONG','is_active'=>1])->assertStatus(422);
        $this->get(route('safety-shop.locations.edit',$this->location))->assertOk()->assertSee('Edit location');
    }
    public function test_customer_master_supports_search_create_and_edit_without_deleting_history()
    {
        $this->login()->post(route('safety-shop.customers.store'),['name'=>'Al Noor Trading','phone'=>'+966 55 987 6543','email'=>'buyer@alnoor.test','address'=>'Riyadh','is_active'=>1])->assertSessionHasNoErrors();
        $customer=Customer::firstOrFail();
        $this->assertSame('+966559876543',$customer->phone);
        $this->get(route('safety-shop.customers.index',['search'=>'9876']))->assertOk()->assertSee('Al Noor Trading')->assertSee('buyer@alnoor.test');
        $this->put(route('safety-shop.customers.update',$customer),['name'=>'Al Noor Safety Trading','phone'=>'+966559876543','email'=>'accounts@alnoor.test','address'=>'Riyadh Industrial Area','is_active'=>0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_customers',['id'=>$customer->id,'name'=>'Al Noor Safety Trading','is_active'=>0]);
        $this->assertDatabaseHas('activity_logs',['action'=>'Updated safety shop customer','subject'=>'Al Noor Safety Trading']);
    }
    public function test_only_super_admin_manages_receipt_company_details_and_placeholder()
    {
        $companyAdmin=User::create(['name'=>'Company Admin','username'=>'company_admin_branding','email'=>'company_admin_branding@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->get(route('document-branding.edit'))->assertForbidden();
        $this->get(route('companies.index'))->assertOk()->assertDontSee('Document branding');
        $this->login($this->manager)->get(route('document-branding.edit'))->assertForbidden();
        $this->login()->get(route('document-branding.edit'))->assertOk()->assertSee('professional “SS” placeholder');
        $this->put(route('document-branding.update'),['company_name'=>'Professional Safety Trading','tagline'=>'Protection at work','vat_number'=>'300000000000003','commercial_registration'=>'1012345678','phone'=>'+966 11 000 0000','email'=>'sales@example.com','website'=>'https://example.com','address'=>'King Fahd Road','city'=>'Riyadh','postal_code'=>'12345','footer_text'=>'Thank you for choosing us.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_receipt_settings',['company_name'=>'Professional Safety Trading','vat_number'=>'300000000000003']);
        $this->assertDatabaseHas('activity_logs',['action'=>'Updated global document branding']);
        $this->receive(); $this->post(route('safety-shop.sales.store'),$this->sale())->assertSessionHasNoErrors();
        $this->get(route('safety-shop.sales.show',Sale::firstOrFail()))->assertOk()->assertSee('PROFESSIONAL SAFETY TRADING')->assertSee('VAT 300000000000003')->assertSee('King Fahd Road');
    }
    public function test_low_stock_and_csv_filters_match_results_and_escape_formulas()
    {
        $this->receive(2);
        $this->product->update(['name'=>'=Unsafe spreadsheet formula']);
        $this->get(route('safety-shop.index',['low'=>1]))->assertOk()->assertSee('Low stock')->assertSee('HELMET-01');
        $response=$this->get(route('safety-shop.export',['low'=>1]))->assertOk();
        $this->assertStringContainsString("'=Unsafe spreadsheet formula",$response->streamedContent());
        $this->get(route('safety-shop.export',['report'=>'movements','type'=>'receipt']))->assertOk();
        $this->get(route('safety-shop.stock.index',['location_id'=>$this->location->id]))->assertOk()->assertSee('Test stock movement');
        $this->get(route('safety-shop.sales.create'))->assertOk()->assertSee('Scan / type barcode');
        $this->getJson(route('safety-shop.barcode',['barcode'=>'unknown','location_id'=>$this->location->id]))->assertNotFound();
    }
}
