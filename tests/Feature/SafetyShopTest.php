<?php
namespace Tests\Feature;
use App\Models\User;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use App\Modules\SafetyShop\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
class SafetyShopTest extends TestCase
{
    use RefreshDatabase;
    private $admin; private $manager; private $product; private $location; private $destination; private $supplier;
    protected function setUp(): void
    {
        parent::setUp();
        config(['safety_shop.enabled'=>true,'invoicing.enabled'=>false,'zatca.enabled'=>false]);
        $this->admin=User::create(['name'=>'Shop Admin','username'=>'shopadmin','email'=>'shopadmin@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'super_admin','is_active'=>true]);
        $this->manager=User::create(['name'=>'Shop Reader','username'=>'shopreader','email'=>'shopreader@test.local','password'=>Hash::make('Strong-password-123'),'role'=>'manager','is_active'=>true]);
        $this->location=Master::create(['type'=>'location','name'=>'Shop counter']);
        $this->destination=Master::create(['type'=>'location','name'=>'Warehouse']);
        $this->supplier=Master::create(['type'=>'supplier','name'=>'PPE Supplier']);
        $this->product=Product::create(['sku'=>'HELMET-01','barcode'=>'001234567890','name'=>'Safety helmet','unit'=>'piece','cost_cents'=>1000,'price_cents'=>2500,'reorder_level'=>3]);
    }
    private function login($user=null) { $user=$user??$this->admin; return $this->withSession(['password_hash_web'=>$user->getAuthPassword()])->actingAs($user); }
    private function movement($type='receipt',$quantity=10,$extra=[])
    {
        return array_merge(['request_key'=>(string)Str::uuid(),'type'=>$type,'product_id'=>$this->product->id,'location_id'=>$this->location->id,'quantity'=>$quantity,'movement_date'=>now()->format('Y-m-d'),'notes'=>'Test stock movement','recipient'=>'Walk-in customer'],$extra);
    }
    private function sale($quantity=2,$extra=[])
    {
        return array_merge(['request_key'=>(string)Str::uuid(),'location_id'=>$this->location->id,'customer'=>'Counter customer','payment_method'=>'cash','paid'=>'60.00','lines'=>[['product_id'=>$this->product->id,'quantity'=>$quantity,'price_cents'=>2500]]],$extra);
    }
    private function receive($quantity=10) { $this->login()->post(route('safety-shop.stock.store'),$this->movement('receipt',$quantity))->assertSessionHasNoErrors(); }
    public function test_receipt_issue_return_and_adjustment_keep_ledger_and_balances()
    {
        $this->receive();
        foreach ([['issue',4],['return',2],['adjustment',-1]] as $step) $this->post(route('safety-shop.stock.store'),$this->movement($step[0],$step[1]))->assertSessionHasNoErrors();
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
        $this->post(route('safety-shop.stock.store'),$this->movement())->assertForbidden();
        $this->get(route('safety-shop.sales.create'))->assertForbidden();
        $this->post(route('safety-shop.sales.store'),$this->sale())->assertForbidden();
        $this->get(route('safety-shop.products.create'))->assertForbidden();
        $this->get(route('safety-shop.categories.index'))->assertOk();
        $this->login()->get(route('safety-shop.index'))->assertOk()->assertDontSee('Invoice system')->assertDontSee('ZATCA integration');
        config(['safety_shop.enabled'=>false]);
        $this->get(route('safety-shop.index'))->assertNotFound();
        $this->post(route('safety-shop.stock.store'),$this->movement())->assertNotFound();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Stock overview')->assertSee('Monthly salaries');
    }
    public function test_product_barcode_and_prices_can_be_saved_and_barcode_is_unique()
    {
        $payload=['sku'=>'GLOVE-01','barcode'=>'000001','name'=>'Safety gloves','unit'=>'pair','cost'=>'3.07','price'=>'8.90','reorder_level'=>2,'is_active'=>1];
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
        $sale=Sale::firstOrFail(); $this->assertSame(5000,$sale->total_cents); $this->assertSame(6000,$sale->paid_cents);
        $this->assertDatabaseHas('safety_shop_stocks',['product_id'=>$this->product->id,'quantity'=>8]);
        $this->assertDatabaseHas('safety_shop_movements',['reference'=>'SALE-'.$sale->id,'quantity'=>-2]);
        $this->product->update(['name'=>'Updated helmet','price_cents'=>3500]);
        $this->get(route('safety-shop.sales.show',$sale))->assertOk()->assertSee('Safety helmet')->assertSee('Print receipt')->assertSee('10.00');
        $this->post(route('safety-shop.sales.store'),$payload)->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('safety_shop_sales',1);
        $this->assertDatabaseCount('invoices',0);
    }
    public function test_failed_multiline_sale_rolls_back_all_stock_and_sales()
    {
        $this->receive();
        $second=Product::create(['sku'=>'EMPTY','barcode'=>'EMPTY','name'=>'Empty product','unit'=>'piece','price_cents'=>100]);
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
    public function test_feature_directories_have_separate_pages_and_master_types()
    {
        $this->login();
        foreach (['products.index','categories.index','categories.create','suppliers.index','suppliers.create','locations.index','locations.create','stock.index','stock.create','sales.index','sales.create','reports.index','products.create'] as $page) $this->get(route('safety-shop.'.$page))->assertOk();
        $this->post(route('safety-shop.suppliers.store'),['type'=>'category','name'=>'Separate supplier','is_active'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('safety_shop_masters',['type'=>'supplier','name'=>'Separate supplier']);
        $this->get(route('safety-shop.categories.index'))->assertDontSee('Separate supplier')->assertDontSee('Save category');
        $this->put(route('safety-shop.categories.update',$this->supplier),['name'=>'Wrong category','is_active'=>1])->assertStatus(422);
        $this->get(route('safety-shop.locations.edit',$this->location))->assertOk()->assertSee('Edit location');
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
