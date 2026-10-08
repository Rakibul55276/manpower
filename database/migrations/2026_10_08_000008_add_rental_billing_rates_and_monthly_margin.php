<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddRentalBillingRatesAndMonthlyMargin extends Migration {
 public function up(){Schema::table('employees',function(Blueprint $t){$t->unsignedInteger('po_rate_cents')->default(0)->after('overtime_rate_cents');$t->unsignedBigInteger('company_cost_cents')->default(0)->after('po_rate_cents');});Schema::table('payrolls',function(Blueprint $t){$t->unsignedBigInteger('po_revenue_cents')->default(0)->after('overtime_pay_cents');$t->unsignedBigInteger('employee_cost_cents')->default(0)->after('po_revenue_cents');$t->unsignedBigInteger('company_cost_cents')->default(0)->after('employee_cost_cents');$t->bigInteger('margin_cents')->default(0)->after('company_cost_cents');});}
 public function down(){Schema::table('payrolls',fn(Blueprint $t)=>$t->dropColumn(['po_revenue_cents','employee_cost_cents','company_cost_cents','margin_cents']));Schema::table('employees',fn(Blueprint $t)=>$t->dropColumn(['po_rate_cents','company_cost_cents']));}
}
