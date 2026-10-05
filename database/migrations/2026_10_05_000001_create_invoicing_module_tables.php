<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateInvoicingModuleTables extends Migration
{
    public function up()
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('legal_name_ar')->nullable();
            $table->string('vat_number', 15);
            $table->string('commercial_registration', 30);
            $table->string('address');
            $table->string('city', 100);
            $table->string('postal_code', 10);
            $table->string('country_code', 2)->default('SA');
            $table->string('invoice_prefix', 20)->default('DEMO');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->boolean('demo_mode')->default(true);
            $table->string('zatca_environment')->default('disabled');
            $table->timestamps();
        });
        Schema::create('invoice_customers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('name_ar')->nullable();
            $table->string('customer_type', 10)->default('business');
            $table->string('vat_number', 15)->nullable(); $table->string('commercial_registration', 30)->nullable();
            $table->string('email')->nullable(); $table->string('phone', 30)->nullable();
            $table->string('address'); $table->string('city', 100); $table->string('postal_code', 10)->nullable();
            $table->string('country_code', 2)->default('SA'); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->index(['customer_type', 'is_active']);
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id(); $table->string('sku', 50)->unique(); $table->string('name'); $table->string('name_ar')->nullable();
            $table->string('unit_code', 10)->default('PCE'); $table->unsignedBigInteger('unit_price_cents');
            $table->string('tax_category', 10)->default('standard'); $table->unsignedInteger('tax_rate_units')->default(1500);
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->string('invoice_number')->nullable()->unique();
            $table->string('uuid', 36)->unique(); $table->string('document_type', 20)->default('invoice');
            $table->string('invoice_type', 10)->default('standard'); $table->foreignId('customer_id')->constrained('invoice_customers')->restrictOnDelete();
            $table->foreignId('reference_invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            $table->date('issue_date'); $table->string('currency', 3)->default('SAR');
            $table->unsignedBigInteger('subtotal_cents'); $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents'); $table->unsignedBigInteger('total_cents');
            $table->string('status', 20)->default('draft'); $table->string('zatca_status', 30)->default('not_submitted');
            $table->text('notes')->nullable(); $table->text('zatca_message')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete(); $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete(); $table->timestamp('paid_at')->nullable();
            $table->timestamps(); $table->index(['issue_date', 'status']); $table->index(['invoice_type', 'zatca_status']);
        });
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id(); $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('invoice_items')->restrictOnDelete();
            $table->string('description'); $table->unsignedInteger('quantity_units'); $table->string('unit_code', 10);
            $table->unsignedBigInteger('unit_price_cents'); $table->unsignedBigInteger('line_subtotal_cents');
            $table->unsignedBigInteger('discount_cents')->default(0); $table->string('tax_category', 10);
            $table->unsignedInteger('tax_rate_units'); $table->unsignedBigInteger('tax_cents'); $table->unsignedBigInteger('line_total_cents');
            $table->timestamps();
        });
        Schema::create('invoice_events', function (Blueprint $table) {
            $table->id(); $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); $table->text('details')->nullable(); $table->timestamps();
        });

        $now = now();
        DB::table('invoice_settings')->insert(['legal_name' => 'Manpower Demo Company LLC', 'legal_name_ar' => 'شركة القوى العاملة التجريبية', 'vat_number' => '300000000000003', 'commercial_registration' => '1010000000', 'address' => 'King Fahd Road, Demo Building', 'city' => 'Riyadh', 'postal_code' => '12345', 'country_code' => 'SA', 'invoice_prefix' => 'DEMO', 'next_number' => 1, 'demo_mode' => true, 'zatca_environment' => 'disabled', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('invoice_customers')->insert([
            ['name'=>'Eastern Engineering Ltd','name_ar'=>'شركة الهندسة الشرقية','customer_type'=>'business','vat_number'=>'310000000000003','commercial_registration'=>'2050000001','email'=>'accounts@eastern.example','phone'=>'+966 13 800 1000','address'=>'Industrial City','city'=>'Dammam','postal_code'=>'32241','country_code'=>'SA','is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Riyadh Facility Services','name_ar'=>'خدمات مرافق الرياض','customer_type'=>'business','vat_number'=>'310000000000011','commercial_registration'=>'1010000002','email'=>'finance@facility.example','phone'=>'+966 11 800 2000','address'=>'Olaya District','city'=>'Riyadh','postal_code'=>'12214','country_code'=>'SA','is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Walk-in Customer','name_ar'=>'عميل نقدي','customer_type'=>'consumer','vat_number'=>null,'commercial_registration'=>null,'email'=>null,'phone'=>null,'address'=>'Riyadh','city'=>'Riyadh','postal_code'=>'12345','country_code'=>'SA','is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('invoice_items')->insert([
            ['sku'=>'MP-HOUR','name'=>'Manpower service hour','name_ar'=>'ساعة خدمة قوى عاملة','unit_code'=>'HUR','unit_price_cents'=>3500,'tax_category'=>'standard','tax_rate_units'=>1500,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['sku'=>'SUP-MONTH','name'=>'Monthly supervision service','name_ar'=>'خدمة إشراف شهرية','unit_code'=>'MON','unit_price_cents'=>500000,'tax_category'=>'standard','tax_rate_units'=>1500,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['sku'=>'TRANSPORT','name'=>'Employee transportation','name_ar'=>'نقل الموظفين','unit_code'=>'PCE','unit_price_cents'=>75000,'tax_category'=>'standard','tax_rate_units'=>1500,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['sku'=>'ADMIN','name'=>'Administration service','name_ar'=>'خدمة إدارية','unit_code'=>'PCE','unit_price_cents'=>100000,'tax_category'=>'standard','tax_rate_units'=>1500,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down()
    {
        foreach (['invoice_events','invoice_lines','invoices','invoice_items','invoice_customers','invoice_settings'] as $table) Schema::dropIfExists($table);
    }
}
