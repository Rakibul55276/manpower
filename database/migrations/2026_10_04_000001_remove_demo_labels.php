<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemoveDemoLabels extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            foreach ([
                'companies' => ['name'],
                'employees' => ['name', 'previous_experience'],
                'timesheets' => ['notes'],
                'payrolls' => ['employee_name', 'company_name', 'notes'],
            ] as $table => $columns) {
                foreach ($columns as $column) {
                    DB::table($table)->where($column, 'like', '%[Demo] %')
                        ->update([$column => DB::raw("REPLACE(`{$column}`, '[Demo] ', '')")]);
                }
            }
        });
    }

    public function down()
    {
        // Original labels cannot be inferred reliably after records are edited.
    }
}
