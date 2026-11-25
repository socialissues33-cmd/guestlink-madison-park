<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('qr_token');
            }
        });

        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'guest_rating')) {
                $table->unsignedTinyInteger('guest_rating')->nullable()->after('auto_reply_bot');
                $table->text('guest_feedback')->nullable()->after('guest_rating');
            }
        });

        Schema::table('food_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('food_orders', 'guest_rating')) {
                $table->unsignedTinyInteger('guest_rating')->nullable()->after('processed_by');
                $table->text('guest_feedback')->nullable()->after('guest_rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'checked_in_at')) {
                $table->dropColumn('checked_in_at');
            }
        });

        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'guest_rating')) {
                $table->dropColumn(['guest_rating', 'guest_feedback']);
            }
        });

        Schema::table('food_orders', function (Blueprint $table) {
            if (Schema::hasColumn('food_orders', 'guest_rating')) {
                $table->dropColumn(['guest_rating', 'guest_feedback']);
            }
        });
    }
};
