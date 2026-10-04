<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCvFieldsToEmployees extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nationality', 100)->nullable()->after('phone');
            $table->string('personal_email')->nullable()->after('nationality');
            $table->text('professional_summary')->nullable()->after('personal_email');
            $table->text('education')->nullable()->after('professional_summary');
            $table->text('skills')->nullable()->after('education');
        });

        foreach (DB::table('employees')->select('id', 'previous_experience', 'name')->get() as $employee) {
            $old = json_decode($employee->previous_experience ?: '[]', true) ?: [];
            $structured = collect($old)->map(function ($entry, $index) {
                if (is_array($entry)) { return $entry; }
                return ['company_name' => '', 'location' => '', 'position' => 'Previous role '.($index + 1), 'start_date' => null, 'end_date' => null, 'responsibilities' => $entry];
            })->values()->all();
            DB::table('employees')->where('id', $employee->id)->update([
                'nationality' => 'Not specified',
                'personal_email' => 'employee'.$employee->id.'@manpower.local',
                'professional_summary' => 'Experienced workforce professional with a record of safe, reliable work and effective teamwork.',
                'education' => 'Education and training details to be updated.',
                'skills' => 'Safety awareness, teamwork, communication, daily reporting',
                'previous_experience' => json_encode($structured),
            ]);
        }
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'personal_email', 'professional_summary', 'education', 'skills']);
        });
    }
}
