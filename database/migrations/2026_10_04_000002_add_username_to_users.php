<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUsernameToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $base = $user->email === 'admin@manpower.local' ? 'admin'
                : ($user->email === 'manager@manpower.local' ? 'manager' : preg_replace('/[^A-Za-z0-9_-]/', '_', strstr($user->email, '@', true)));
            $username = $base ?: 'user';
            $suffix = 1;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.'_'.(++$suffix);
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
}
