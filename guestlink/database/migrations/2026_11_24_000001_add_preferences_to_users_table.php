<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'theme_preference')) {
                $table->string('theme_preference', 10)->default('dark')->after('is_active');
            }
            if (!Schema::hasColumn('users', 'bot_auto_reply_enabled')) {
                $table->boolean('bot_auto_reply_enabled')->default(true)->after('theme_preference');
            }
            if (!Schema::hasColumn('users', 'sidebar_collapsed')) {
                $table->boolean('sidebar_collapsed')->default(false)->after('bot_auto_reply_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(
                ['theme_preference', 'bot_auto_reply_enabled', 'sidebar_collapsed'],
                fn ($col) => Schema::hasColumn('users', $col)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
