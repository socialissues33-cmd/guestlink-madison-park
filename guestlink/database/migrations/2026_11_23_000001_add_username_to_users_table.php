<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 64)->nullable()->unique()->after('name');
            });
        }

        // Backfill usernames from email prefix & set admin credentials to admin / admin123
        $users = DB::table('users')->get();
        foreach ($users as $u) {
            $prefix = strtolower(explode('@', (string) $u->email)[0] ?: ('user' . $u->id));
            $update = ['username' => $u->username ?: $prefix];
            if ($u->email === 'admin@madisonpark.com' || $prefix === 'admin') {
                $update['username'] = 'admin';
                $update['password'] = Hash::make('admin123');
            }
            DB::table('users')->where('id', $u->id)->update($update);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
