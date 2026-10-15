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
        config(['safety_shop.enabled'=>true,'invoicing.enabled'=>false,'zatca.enabled'=>false,'manpower.enabled'=>true]);
        $this->admin=User::create(['name'=>'Shop Admin','username'=>'shopadmin','email'=>'shopadmin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'super_admin','is_active'=>true]);
        $this->manager=User::create(['name'=>'Shop Reader','username'=>'shopreader','email'=>'shopreader@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'manager','is_active'=>true]);
        $this->company=Company::create(['name'=>'Safety Shop Test Company','location'=>'Riyadh']);
        $this->manager->update(['company_id'=>$this->company->id]);
        $this->location=Master::create(['company_id'=>$this->company->id,'type'=>'location','name'=>'Shop counter']);
        $this->destination=Master::create(['company_id'=>$this->company->id,'type'=>'location','name'=>'Warehouse']);
        $this->supplier=Master::create(['company_id'=>$this->company->id,'type'=>'supplier','name'=>'PPE Supplier']);
        $this->product=Product::create(['company_id'=>$this->company->id,'sku'=>'HELMET-01','barcode'=>'001234567890','name'=>'Safety helmet','unit'=>'piece','cost_cents'=>1000,'price_cents'=>2500,'reorder_level'=>3]);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::create(['company_id'=>$this->company->id,'company_name'=>$this->company->name,'company_name_ar'=>'شركة اختبار متجر السلامة','vat_number'=>'314482276100003','commercial_registration'=>'1010000000','address'=>'Industrial Area','building_number'=>'1000','additional_number'=>'2000','street'=>'King Fahd Road','district'=>'Industrial District','city'=>'Riyadh','postal_code'=>'12345','country_code'=>'SA']);
    }
    private function login($user=null) { $user=$user??$this->admin; return $this->withSession(['password_hash_web'=>$user->getAuthPassword()])->actingAs($user); }
    private function movement($type='receipt',$quantity=10,$extra=[])
    {
        return array_merge(['company_id'=>$this->company->id,'request_key'=>(string)Str::uuid(),'type'=>$type,'product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>$quantity,'movement_date'=>now()->format('Y-m-d'),'notes'=>'Test stock movement','recipient'=>'Walk-in customer'],$extra);
    }
    private function sale($quantity=2,$extra=[])
    {
        return array_merge(['request_key'=>(string)Str::uuid(),'location_id'=>$this->location->id,'supply_date'=>now()->subDay()->toDateString(),'customer'=>'Counter customer','customer_phone'=>'+966 55 123 4567','payment_method'=>'cash','paid'=>'60.00','lines'=>[['product_id'=>$this->product->id,'quantity'=>$quantity,'price_cents'=>2500]]],$extra);
    }
    private function receive($quantity=10) { $this->login()->post(route('safety-shop.stock.store'),$this->movement('receipt',$quantity))->assertSessionHasNoErrors(); }
    public function test_company_arabic_name_overrides_invoice_branding()
    {
        $this->company->update(['name_ar'=>'شركة اختبار السلامة']);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::updateOrCreate(['company_id'=>$this->company->id],[
            'company_name'=>$this->company->name,
            'company_name_ar'=>'اسم قديم',
        ]);

        $this->login($this->manager);
        $branding=\App\Services\Documents::branding();

        $this->assertSame('شركة اختبار السلامة',$branding->company_name_ar);
    }
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
        $this->get('/company-branding')->assertNotFound();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Full analytics & audit')->assertDontSee('Company inventory overview')->assertSee('Safety Shop analytics')->assertSee('Net revenue excl. VAT')->assertSee('Inventory value')->assertSee('Monthly movements');
        $this->login($this->manager)->get(route('dashboard'))->assertOk()->assertSee('Safety Shop analytics')->assertSee('Net revenue excl. VAT');
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
        $this->login($this->manager)->get(route('safety-shop.index'))->assertOk();
        $this->get(route('safety-shop.sales.create'))->assertOk();
        $this->get(route('safety-shop.returns.index'))->assertOk();
        $this->get(route('safety-shop.returns.create'))->assertOk();
        $this->get(route('safety-shop.customers.index'))->assertOk();
        $this->get(route('safety-shop.customers.create'))->assertOk();
        $this->get(route('safety-shop.products.create'))->assertOk();
        $this->get(route('safety-shop.categories.index'))->assertOk();
        $this->get(route('safety-shop.reports.index'))->assertOk();
        $issuer=User::create(['name'=>'Shop Issuer','username'=>'shopissuer','email'=>'shopissuer@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'issuer','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($issuer)->get(route('safety-shop.sales.create'))->assertOk();
        $this->get(route('safety-shop.stock.create'))->assertOk();
        $this->get(route('safety-shop.reports.index'))->assertForbidden();
        $this->post(route('safety-shop.stock.store'),$this->movement('receipt',5))->assertSessionHasNoErrors();
        $this->post(route('safety-shop.sales.store'),$this->sale(1))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_sales',['created_by'=>$issuer->id]);
        $this->login($this->manager);
        $this->get(route('safety-shop.index'))->assertOk()->assertDontSee('Invoice system')->assertDontSee('ZATCA integration');
        config(['safety_shop.enabled'=>false]);
        $this->get(route('safety-shop.index'))->assertNotFound();
        $this->post(route('safety-shop.stock.store'),$this->movement())->assertNotFound();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Stock overview')->assertSee('Monthly salaries');
    }
    public function test_bulk_customer_invoice_keeps_eight_items_on_one_page_and_moves_ninth_to_page_two()
    {
        $companyAdmin=User::create(['name'=>'Bulk Sales Admin','username'=>'bulk_sales_admin','email'=>'bulk_sales_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->company->update(['name_ar'=>'شركة متجر السلامة للاختبار']);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::updateOrCreate(['company_id'=>$this->company->id],['company_name'=>$this->company->name,'vat_number'=>'314482276100003','commercial_registration'=>'7052965014']);
        $customer=Customer::create(['company_id'=>$this->company->id,'customer_type'=>'company','name'=>'Al Yamama Industrial Services','name_ar'=>'شركة اليمامة للخدمات الصناعية','contact_person'=>'Purchasing Team','phone'=>'+966133632514','vat_number'=>'314716033700003','commercial_registration'=>'2050156215','address'=>'Third Industrial Area, Jubail','country_code'=>'SA','approval_status'=>'approved','approved_by'=>$companyAdmin->id,'approved_at'=>now(),'is_active'=>true]);
        $this->login($companyAdmin);
        $this->post(route('safety-shop.stock.store'),$this->movement('receipt',10))->assertSessionHasNoErrors();
        $payload=$this->sale(2,['customer_id'=>$customer->id,'customer_type'=>'company','customer'=>'Al Yamama Industrial Services','tax_rate'=>'15']);
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasNoErrors();
        $sale=Sale::latest('id')->firstOrFail();
        $baseLine=$sale->lines()->firstOrFail();
        foreach(range(2,8) as $number){$copy=$baseLine->replicate();$copy->sku='BULK-'.$number;$copy->name='Safety invoice item '.$number;$copy->save();}
        $this->assertCount(8,$sale->fresh()->lines);
        $this->assertSame('شركة اليمامة للخدمات الصناعية',$sale->customer_name_ar);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('شركة متجر السلامة للاختبار')->assertSee('شركة اليمامة للخدمات الصناعية');
        $response=$this->get(route('safety-shop.sales.pdf',$sale))->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{8}$/',$sale->invoice_number);
        $this->assertSame(1,$sale->invoice_sequence_number);
        $this->assertSame('standard',$sale->invoice_type);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('TAX INVOICE / فاتورة ضريبية')->assertDontSee('SIMPLIFIED TAX INVOICE');
        $this->assertStringContainsString($sale->invoice_number.'.pdf', $response->headers->get('Content-Disposition'));
        $pdf=new \setasign\Fpdi\Fpdi;
        $this->assertSame(1,$pdf->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($response->getContent())));
        $ninth=$baseLine->replicate();$ninth->sku='BULK-9';$ninth->name='Safety invoice item 9';$ninth->save();
        $this->assertCount(9,$sale->fresh()->lines);
        $nineItemResponse=$this->get(route('safety-shop.sales.pdf',$sale))->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->assertSame(2,$pdf->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($nineItemResponse->getContent())));
    }
    public function test_phase_one_uses_one_controlled_sequence_and_drafts_do_not_consume_it()
    {
        $companyAdmin=User::create(['name'=>'Phase One Admin','username'=>'phase_one_admin','email'=>'phase_one_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->post(route('safety-shop.stock.store'),$this->movement('receipt',20))->assertSessionHasNoErrors();

        $this->post(route('safety-shop.sales.store'),$this->sale(1,['submit_action'=>'draft','paid'=>'0.00']))->assertSessionHasNoErrors();
        $draft=Sale::where('status','draft')->firstOrFail();
        $this->assertNull($draft->invoice_sequence_number);

        $this->post(route('safety-shop.sales.store'),$this->sale(1,['customer_phone'=>'+966 55 111 1111']))->assertSessionHasNoErrors();
        $this->post(route('safety-shop.sales.store'),$this->sale(1,['customer_phone'=>'+966 55 222 2222']))->assertSessionHasNoErrors();
        $finalized=Sale::where('status','finalized')->orderBy('invoice_sequence_number')->get();
        $this->assertSame([1,2],$finalized->pluck('invoice_sequence_number')->all());
        $this->assertSame(['INV-0001-00000001','INV-0001-00000002'],$finalized->pluck('invoice_number')->all());
        $this->assertTrue($finalized->every(fn($sale)=>$sale->invoice_type==='simplified'));
        $this->get(route('safety-shop.sales.show',$finalized->first()))->assertOk()->assertSee('SIMPLIFIED TAX INVOICE / فاتورة ضريبية مبسطة');
    }
    public function test_phase_one_blocks_final_invoice_when_legal_seller_identity_is_incomplete_but_allows_draft()
    {
        $companyAdmin=User::create(['name'=>'Phase One Validation Admin','username'=>'phase_one_validation','email'=>'phase_one_validation@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::where('company_id',$this->company->id)->update(['vat_number'=>null]);
        $this->login($companyAdmin)->post(route('safety-shop.stock.store'),$this->movement('receipt',5))->assertSessionHasNoErrors();

        $this->post(route('safety-shop.sales.store'),$this->sale(1,['submit_action'=>'draft','paid'=>'0.00']))->assertSessionHasNoErrors();
        $this->post(route('safety-shop.sales.store'),$this->sale(1))->assertSessionHasErrors('location_id');
        $this->assertDatabaseCount('safety_shop_sales',1);
        $this->assertDatabaseHas('safety_shop_sales',['status'=>'draft']);
        $this->assertDatabaseCount('safety_shop_invoice_sequences',0);
    }
    public function test_zatca_foundation_prepares_immutable_ubl_xml_with_icv_hash_chain_and_no_live_submission()
    {
        config(['zatca.enabled'=>true]);
        $companyAdmin=User::create(['name'=>'ZATCA Admin','username'=>'zatca_admin','email'=>'zatca_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->post(route('safety-shop.stock.store'),$this->movement('receipt',10))->assertSessionHasNoErrors();
        $this->post(route('safety-shop.sales.store'),$this->sale(1,['customer_phone'=>'+966 55 333 3333']))->assertSessionHasNoErrors();
        $sale=Sale::where('status','finalized')->firstOrFail();

        $this->get(route('safety-shop.zatca.index'))->assertOk()->assertSee('Live FATOORA connection is disabled')->assertSee($sale->invoice_number);
        $this->post(route('safety-shop.zatca.prepare',$sale))->assertSessionHasNoErrors()->assertRedirect(route('safety-shop.zatca.index'));
        $document=\App\Modules\Zatca\Models\ZatcaDocument::firstOrFail();
        $this->assertSame(1,$document->icv);
        $this->assertSame('reporting',$document->submission_mode);
        $this->assertSame('prepared',$document->status);
        $this->assertSame(64,strlen($document->invoice_hash));
        $this->assertStringContainsString('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',$document->unsigned_xml);
        $this->assertStringContainsString($sale->invoice_number,$document->unsigned_xml);
        $this->assertStringContainsString($document->uuid,$document->unsigned_xml);
        $this->assertStringContainsString('name="0200000"',$document->unsigned_xml);
        $this->assertSame('pending',data_get($document->validation_results,'zatca_sdk.status'));

        $sdk=app(\App\Modules\Zatca\Services\ZatcaSdkService::class);
        if($sdk->available()){
            $sdkResult=$sdk->validate($document);
            $this->assertSame('passed',$sdkResult['status'],trim(($sdkResult['output']??'')."\n".($sdkResult['error']??'')));
        }

        $this->post(route('safety-shop.zatca.prepare',$sale))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('zatca_documents',1);
        $this->get(route('safety-shop.zatca.xml',$document))->assertOk()->assertHeader('Content-Type','application/xml; charset=UTF-8');
        $this->post(route('safety-shop.zatca.csr'))->assertSessionHasNoErrors();
        $device=\App\Modules\Zatca\Models\ZatcaDevice::firstOrFail();
        $this->assertSame('csr_ready',$device->status);
        $this->assertStringContainsString('BEGIN CERTIFICATE REQUEST',$device->csr);
        $this->assertNotEmpty($device->getRawOriginal('private_key_encrypted'));
        $this->assertStringNotContainsString('BEGIN PRIVATE KEY',$device->getRawOriginal('private_key_encrypted'));
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
    public function test_product_gets_a_company_scoped_internal_barcode_when_left_blank()
    {
        $companyAdmin=User::create(['name'=>'Catalog Admin','username'=>'catalog_admin','email'=>'catalog_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $payload=['company_id'=>$this->company->id,'sku'=>'AUTO-01','barcode'=>'','name'=>'Auto barcode gloves','unit'=>'pair','cost'=>'3.07','price'=>'8.90','reorder_level'=>2,'is_active'=>1];
        $this->login($companyAdmin)->post(route('safety-shop.products.store'),$payload)->assertSessionHasNoErrors();

        $product=Product::where('sku','AUTO-01')->firstOrFail();
        $this->assertMatchesRegularExpression('/^2\d{12}$/',$product->barcode);
        $this->assertSame(str_pad((string)$this->company->id,5,'0',STR_PAD_LEFT),substr($product->barcode,1,5));

        $body=substr($product->barcode,0,12); $sum=0;
        foreach(str_split($body) as $index=>$digit)$sum+=(int)$digit*($index%2===0?1:3);
        $this->assertSame((10-($sum%10))%10,(int)substr($product->barcode,-1));
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
    public function test_sale_allows_credit_and_partial_payment_but_rejects_tampering_and_non_cash_overpayment()
    {
        $this->admin->update(['role'=>'admin','company_id'=>$this->company->id]);
        $this->receive();
        $this->post(route('safety-shop.sales.store'),$this->sale(2,['paid'=>'1.00']))->assertSessionHasNoErrors();
        $partial=Sale::firstOrFail();
        $this->assertSame('partially_paid',$partial->payment_status);
        $this->assertSame(4900,$partial->balance_due_cents);
        $payload=$this->sale(); $payload['lines'][0]['price_cents']=1;
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('lines');
        $payload=$this->sale(); $payload['lines'][]=$payload['lines'][0];
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('lines.0.product_id');
        $this->post(route('safety-shop.sales.store'),$this->sale(2,['payment_method'=>'card']))->assertSessionHasErrors('paid');
        $this->assertDatabaseCount('safety_shop_sales',1); $this->assertSame(8,(int)Stock::sum('quantity'));
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
        $this->admin->update(['role'=>'admin','company_id'=>$this->company->id]);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::where('company_id',$this->company->id)->update(['company_name'=>$this->company->name]);
        $this->receive();
        $payload=$this->sale(2,[
            'discount'=>'10.00','tax_rate'=>'15.00','cash_paid'=>'20.00','card_paid'=>'26.00','bank_paid'=>'0.00','invoice_notes'=>'Deliver with the approved purchase order.',
        ]);
        unset($payload['payment_method'],$payload['paid']);
        $this->login()->post(route('safety-shop.sales.store'),$payload)->assertSessionHasNoErrors();
        $sale=Sale::firstOrFail();
        $this->assertSame(5000,$sale->subtotal_cents); $this->assertSame(1000,$sale->discount_cents);
        $this->assertSame(4000,$sale->taxable_cents); $this->assertSame(1500,$sale->tax_rate_units);
        $this->assertSame(600,$sale->tax_cents); $this->assertSame(4600,$sale->total_cents);
        $this->assertSame(2000,$sale->cash_cents); $this->assertSame(2600,$sale->card_cents);
        $this->assertSame('split',$sale->payment_method);
        $this->assertSame('Deliver with the approved purchase order.',$sale->invoice_notes);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('SKU HELMET-01')->assertDontSee('001234567890')->assertSee('Discount (SAR)')->assertSee('VAT Amount')->assertSee('Cash 20.00')->assertSee('Card 26.00')->assertSee('Deliver with the approved purchase order.');

        $invalid=$this->sale(1,['discount'=>'30.00','tax_rate'=>'15','cash_paid'=>'0','card_paid'=>'0','bank_paid'=>'0']);
        unset($invalid['payment_method'],$invalid['paid']);
        $this->post(route('safety-shop.sales.store'),$invalid)->assertSessionHasErrors('discount');
    }
    public function test_receipt_linked_return_restores_stock_and_prevents_over_return()
    {
        config(['zatca.enabled'=>true]);
        $this->admin->update(['role'=>'admin','company_id'=>$this->company->id]);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::where('company_id',$this->company->id)->update(['company_name'=>$this->company->name,'vat_number'=>'314482276100003']);
        $this->receive();
        $this->post(route('safety-shop.sales.store'),$this->sale(2))->assertSessionHasNoErrors();
        $sale=Sale::with('lines')->firstOrFail();

        $this->getJson(route('safety-shop.returns.lookup',['receipt'=>$sale->invoice_number]))
            ->assertOk()->assertJson(['customer'=>'Counter customer','lines'=>[['returnable'=>2]]]);

        $payload=['request_key'=>(string)Str::uuid(),'sale_id'=>$sale->id,'refund_method'=>'cash','reason'=>'Unused item in original condition','lines'=>[['sale_line_id'=>$sale->lines->first()->id,'quantity'=>1,'barcode'=>'001234567890']]];
        $this->post(route('safety-shop.returns.store'),$payload)->assertSessionHasNoErrors();
        $return=SaleReturn::firstOrFail();
        $this->assertStringStartsWith('CN-',$return->credit_note_number);
        $this->assertSame(2500,$return->refund_cents);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>9]);
        $this->assertDatabaseHas('safety_shop_movements',['reference'=>'RETURN-'.$return->id.' / SALE-'.$sale->id,'quantity'=>1]);
        $this->get(route('safety-shop.returns.show',$return))->assertOk()->assertSee($return->credit_note_number)->assertSee($sale->invoice_number)->assertSee('Counter customer')->assertSee('Unused item in original condition');
        $this->get(route('safety-shop.returns.pdf',$return))->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->post(route('safety-shop.zatca.prepare-return',$return))->assertSessionHasNoErrors();
        $zatcaCredit=\App\Modules\Zatca\Models\ZatcaDocument::where('source_type','safety_shop_return')->firstOrFail();
        $this->assertSame('credit_note',$zatcaCredit->document_kind);
        $this->assertStringContainsString('CreditNote',$zatcaCredit->unsigned_xml);
        $this->assertStringContainsString($sale->invoice_number,$zatcaCredit->unsigned_xml);

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
        $companyAdmin=User::create(['name'=>'Customer Admin','username'=>'customer_admin','email'=>'customer_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->post(route('safety-shop.customers.store'),['customer_type'=>'company','name'=>'Al Noor Trading','name_ar'=>'شركة النور للتجارة','contact_person'=>'Purchasing Desk','phone'=>'+966 55 987 6543','email'=>'buyer@alnoor.test','vat_number'=>'300004578656000','address'=>'Riyadh','is_active'=>1])->assertSessionHasNoErrors();
        $customer=Customer::firstOrFail();
        $this->assertSame('+966559876543',$customer->phone);
        $this->assertSame('شركة النور للتجارة',$customer->name_ar);
        $this->get(route('safety-shop.customers.index',['search'=>'النور']))->assertOk()->assertSee('Al Noor Trading')->assertSee('شركة النور للتجارة')->assertSee('buyer@alnoor.test');
        $this->get(route('safety-shop.customers.edit',$customer))->assertOk()->assertSee('شركة النور للتجارة');
        $this->put(route('safety-shop.customers.update',$customer),['customer_type'=>'company','name'=>'Al Noor Safety Trading','name_ar'=>'شركة النور لمعدات السلامة','contact_person'=>'Purchasing Desk','phone'=>'+966559876543','email'=>'accounts@alnoor.test','vat_number'=>'300004578656000','address'=>'Riyadh Industrial Area','is_active'=>0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_customers',['id'=>$customer->id,'name'=>'Al Noor Safety Trading','name_ar'=>'شركة النور لمعدات السلامة','is_active'=>0]);
        $this->assertDatabaseHas('activity_logs',['action'=>'Updated safety shop customer','subject'=>'Al Noor Safety Trading']);
    }
    public function test_company_branding_screen_is_removed_for_every_role()
    {
        $companyAdmin=User::create(['name'=>'Company Admin','username'=>'company_admin_branding','email'=>'company_admin_branding@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->get('/company-branding')->assertNotFound();
        $this->login($this->manager)->get('/company-branding')->assertNotFound();
        $this->login()->get('/company-branding')->assertNotFound();
        $this->assertFileExists(config('documents.letterhead_pdf'));
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
    public function test_barcode_checkout_explains_automatic_scan_and_keeps_manual_fallback()
    {
        $companyAdmin=User::create(['name'=>'Checkout Admin','username'=>'checkout_admin','email'=>'checkout_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->get(route('safety-shop.sales.create'))->assertOk()->assertSee('product adds automatically')->assertSee('field clears')->assertSee('Add manually')->assertDontSee('Payment received')->assertDontSee('name="payment_received"',false);
        $script=File::get(public_path('js/safety-shop/sales/checkout.js'));
        $this->assertStringContainsString("barcode.addEventListener('input', queueAutomaticScan)",$script);
        $this->assertStringContainsString("add.addEventListener('click', event =>",$script);
        $this->assertStringContainsString('scannerSpeed',$script);
        $this->assertStringContainsString('Finalize and lock this invoice?',$script);
        $this->assertStringContainsString("input('submit_action', draftAction ? 'draft' : 'final')",$script);
        $this->assertStringContainsString("barcode.value = ''",$script);
    }
    public function test_only_company_admin_can_delete_unused_catalog_data_and_transactions_must_be_removed_first()
    {
        $companyAdmin=User::create(['name'=>'Catalog Admin','username'=>'catalog_admin','email'=>'catalog_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $unusedCategory=Master::create(['company_id'=>$this->company->id,'type'=>'category','name'=>'Unused category','sku_prefix'=>'UNUSED','is_active'=>true]);
        $unusedProduct=Product::create(['company_id'=>$this->company->id,'sku'=>'UNUSED-0001','name'=>'Unused test item','unit'=>'piece','cost_cents'=>100,'price_cents'=>200,'reorder_level'=>0]);

        $this->login($this->manager)->delete(route('safety-shop.products.destroy',$unusedProduct))->assertForbidden();
        $this->login($companyAdmin)->delete(route('safety-shop.products.destroy',$unusedProduct))->assertRedirect(route('safety-shop.products.index'));
        $this->assertDatabaseMissing('safety_shop_products',['id'=>$unusedProduct->id]);
        $this->delete(route('safety-shop.categories.destroy',$unusedCategory))->assertRedirect(route('safety-shop.categories.index'));
        $this->assertDatabaseMissing('safety_shop_masters',['id'=>$unusedCategory->id]);

        $this->post(route('safety-shop.stock.store'),$this->movement('receipt',2))->assertSessionHasNoErrors();
        $this->delete(route('safety-shop.products.destroy',$this->product))->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('safety_shop_products',['id'=>$this->product->id]);
    }
    public function test_admin_can_void_and_replace_invoice_with_stock_and_audit_links()
    {
        $companyAdmin=User::create(['name'=>'Invoice Admin','username'=>'invoice_admin','email'=>'invoice_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->post(route('safety-shop.stock.store'),$this->movement('receipt',10))->assertSessionHasNoErrors();
        $this->post(route('safety-shop.sales.store'),$this->sale(2))->assertSessionHasNoErrors();
        $original=Sale::firstOrFail();
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>8]);
        $this->post(route('safety-shop.sales.cancel-replace',$original),['cancel_reason'=>'Customer requested an additional item.'])->assertRedirect(route('safety-shop.sales.create',['replace'=>$original->id]));
        $this->assertDatabaseHas('safety_shop_sales',['id'=>$original->id,'status'=>'void','cancelled_by'=>$companyAdmin->id]);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>10]);
        $this->get(route('safety-shop.sales.create',['replace'=>$original->id]))->assertOk()->assertSee('Replacing void invoice')->assertSee($original->invoice_number);
        $replacement=$this->sale(3,['replacement_of_id'=>$original->id,'customer_id'=>$original->customer_id,'paid'=>'100.00']);
        $this->post(route('safety-shop.sales.store'),$replacement)->assertSessionHasNoErrors();
        $new=Sale::where('id','<>',$original->id)->firstOrFail();
        $this->assertSame($original->id,(int)$new->replaces_sale_id);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>7]);
        $this->post(route('safety-shop.sales.store'),$this->sale(1,['replacement_of_id'=>$original->id,'customer_id'=>$original->customer_id]))->assertStatus(422);
    }
    public function test_invoice_draft_does_not_move_stock_and_can_be_reopened_and_finalized()
    {
        $companyAdmin=User::create(['name'=>'Draft Admin','username'=>'draft_admin','email'=>'draft_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::where('company_id',$this->company->id)->update(['company_name'=>$this->company->name]);
        $this->login($companyAdmin)->post(route('safety-shop.stock.store'),$this->movement('receipt',10))->assertSessionHasNoErrors();
        $dueDate=now()->addDays(30)->toDateString();
        $draftPayload=$this->sale(2,['submit_action'=>'draft','cash_paid'=>'0.00','paid'=>'0.00','customer_po_number'=>'PO-DRAFT-01','due_date'=>$dueDate]);
        $this->post(route('safety-shop.sales.store'),$draftPayload)->assertSessionHasNoErrors();
        $draft=Sale::firstOrFail();
        $this->assertSame('draft',$draft->status);
        $this->assertSame('PO-DRAFT-01',$draft->customer_po_number);
        $this->assertSame($dueDate,$draft->due_date->toDateString());
        $this->assertSame(now()->subDay()->toDateString(),$draft->supply_date->toDateString());
        $this->assertStringStartsWith('DRAFT-',$draft->invoice_number);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>10]);
        $this->assertDatabaseMissing('safety_shop_movements',['reference'=>'SALE-'.$draft->id]);
        $this->get(route('safety-shop.sales.show',$draft))->assertOk()->assertSee('receipt-draft-watermark')->assertSee('DRAFT / مسودة')->assertDontSee('Payment Due Date')->assertSee('PO No.')->assertSee('PO-DRAFT-01')->assertSee('رقم أمر الشراء')->assertSee('Edit and finalize draft')->assertSee('Edit draft')->assertSee('Download draft PDF');
        $this->get(route('safety-shop.sales.pdf',$draft))->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->get(route('safety-shop.sales.draft',$draft))->assertOk()->assertSee('Editing draft')->assertSee('checkout-initial-data')->assertSee('Counter customer')->assertSee('+966 55 123 4567')->assertSee('value="'.now()->subDay()->toDateString().'"',false);
        $finalPayload=$this->sale(3,['draft_id'=>$draft->id,'submit_action'=>'final','paid'=>'100.00','due_date'=>$dueDate]);
        $this->post(route('safety-shop.sales.store'),$finalPayload)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('safety_shop_sales',['id'=>$draft->id]);
        $final=Sale::firstOrFail();
        $this->assertSame('finalized',$final->status);
        $this->assertSame(now()->subDay()->toDateString(),$final->supply_date->toDateString());
        $this->assertSame($dueDate,$final->due_date->toDateString());
        $this->assertStringStartsWith('INV-',$final->invoice_number);
        $this->get(route('safety-shop.sales.show',$final))->assertOk()->assertSee(now()->subDay()->format('d-m-Y'))->assertSee('Finalized invoice — locked');
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>7]);
    }
    public function test_invoice_rejects_a_future_supply_date()
    {
        $companyAdmin=User::create(['name'=>'Supply Date Admin','username'=>'supply_date_admin','email'=>'supply_date_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        $this->login($companyAdmin)->post(route('safety-shop.sales.store'),$this->sale(1,['supply_date'=>now()->addDay()->toDateString()]))->assertSessionHasErrors('supply_date');
        $this->assertDatabaseCount('safety_shop_sales',0);
    }
    public function test_invoice_payment_ledger_moves_unpaid_to_partial_and_paid_and_supports_audited_reversal()
    {
        $companyAdmin=User::create(['name'=>'Payment Admin','username'=>'payment_admin','email'=>'payment_admin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'admin','company_id'=>$this->company->id,'is_active'=>true]);
        \App\Modules\SafetyShop\Shared\Models\ReceiptSetting::where('company_id',$this->company->id)->update(['company_name'=>$this->company->name]);
        $this->login($companyAdmin)->post(route('safety-shop.stock.store'),$this->movement('receipt',10))->assertSessionHasNoErrors();
        $this->post(route('safety-shop.sales.store'),$this->sale(2,['paid'=>'0.00']))->assertSessionHasNoErrors();
        $sale=Sale::firstOrFail();
        $this->assertSame('unpaid',$sale->payment_status);
        $this->assertDatabaseCount('safety_shop_sale_payments',0);

        $this->post(route('safety-shop.sales.payments.store',$sale),['payment_date'=>now()->toDateString(),'method'=>'bank','amount'=>'10.00','reference'=>'BANK-001'])->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertSame('partially_paid',$sale->payment_status);
        $this->assertSame(4000,$sale->balance_due_cents);
        $this->assertFalse($sale->payment_received);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('PARTIALLY PAID')->assertSee('مدفوع جزئياً');
        $this->get(route('safety-shop.sales.pdf',$sale))->assertOk()->assertHeader('Content-Type','application/pdf');

        $this->post(route('safety-shop.sales.payments.store',$sale),['payment_date'=>now()->toDateString(),'method'=>'cash','amount'=>'40.00','reference'=>'RCPT-002'])->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertSame('paid',$sale->payment_status);
        $this->assertTrue($sale->payment_received);
        $cashPayment=$sale->payments()->where('method','cash')->firstOrFail();
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('Payment ledger')->assertSee('BANK-001')->assertSee('PAYMENT RECEIVED')->assertSee('تم استلام المبلغ');

        $this->post(route('safety-shop.sales.payments.reverse',[$sale,$cashPayment]),['reversal_reason'=>'Duplicate cash receipt entry'])->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertSame('partially_paid',$sale->payment_status);
        $this->assertFalse($sale->payment_received);
        $this->assertDatabaseHas('safety_shop_sale_payments',['id'=>$cashPayment->id,'reversed_by'=>$companyAdmin->id,'reversal_reason'=>'Duplicate cash receipt entry']);
    }
}
