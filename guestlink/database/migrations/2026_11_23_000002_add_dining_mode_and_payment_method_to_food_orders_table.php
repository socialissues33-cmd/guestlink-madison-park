<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('food_orders', 'dining_mode')) {
                $table->string('dining_mode', 32)->default('room_delivery')->after('status');
            }
            if (!Schema::hasColumn('food_orders', 'payment_method')) {
                $table->string('payment_method', 32)->default('charge_to_room')->after('dining_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            if (Schema::hasColumn('food_orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('food_orders', 'dining_mode')) {
                $table->dropColumn('dining_mode');
            }
        });
    }
};
