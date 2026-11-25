<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('updated_at');
            $table->index('priority');
        });

        Schema::table('food_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('updated_at');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->index(['sender_type', 'is_read']);
            $table->index('created_at');
        });

        Schema::table('ai_triage_results', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['priority']);
        });

        Schema::table('food_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropIndex(['sender_type', 'is_read']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('ai_triage_results', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
