<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('service_requests', 'auto_reply_bot')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->boolean('auto_reply_bot')->default(false)->after('department');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_requests', 'auto_reply_bot')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropColumn('auto_reply_bot');
            });
        }
    }
};
