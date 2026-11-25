<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->index(['room_id', 'created_at'], 'idx_sr_room_created');
            $table->index(['status', 'priority'], 'idx_sr_status_prio');
            $table->index(['department', 'status'], 'idx_sr_dept_status');
            $table->index('updated_at', 'idx_sr_updated');
        });

        Schema::table('food_orders', function (Blueprint $table) {
            $table->index(['room_id', 'status', 'created_at'], 'idx_fo_room_status_created');
            $table->index(['status', 'updated_at'], 'idx_fo_status_updated');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->index(['service_request_id', 'id'], 'idx_tm_req_id');
            $table->index(['sender_type', 'is_read'], 'idx_tm_sender_read');
        });

        Schema::table('request_status_logs', function (Blueprint $table) {
            $table->index(['service_request_id', 'created_at'], 'idx_rsl_req_created');
        });

        Schema::table('food_order_status_logs', function (Blueprint $table) {
            $table->index(['food_order_id', 'created_at'], 'idx_fosl_order_created');
        });

        Schema::table('ai_triage_results', function (Blueprint $table) {
            $table->index(['service_request_id', 'status'], 'idx_atr_req_status');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['is_active', 'room_number'], 'idx_rooms_active_num');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('idx_sr_room_created');
            $table->dropIndex('idx_sr_status_prio');
            $table->dropIndex('idx_sr_dept_status');
            $table->dropIndex('idx_sr_updated');
        });

        Schema::table('food_orders', function (Blueprint $table) {
            $table->dropIndex('idx_fo_room_status_created');
            $table->dropIndex('idx_fo_status_updated');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropIndex('idx_tm_req_id');
            $table->dropIndex('idx_tm_sender_read');
        });

        Schema::table('request_status_logs', function (Blueprint $table) {
            $table->dropIndex('idx_rsl_req_created');
        });

        Schema::table('food_order_status_logs', function (Blueprint $table) {
            $table->dropIndex('idx_fosl_order_created');
        });

        Schema::table('ai_triage_results', function (Blueprint $table) {
            $table->dropIndex('idx_atr_req_status');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_active_num');
        });
    }
};
