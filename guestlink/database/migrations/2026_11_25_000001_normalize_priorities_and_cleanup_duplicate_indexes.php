<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_requests')) {
            DB::statement("UPDATE service_requests SET priority = LOWER(TRIM(priority)) WHERE priority IS NOT NULL");
            DB::statement("UPDATE service_requests SET department = 'Maintenance' WHERE LOWER(TRIM(COALESCE(department, ''))) = 'engineering'");
            DB::statement("UPDATE service_requests SET department = 'Housekeeping' WHERE LOWER(TRIM(COALESCE(department, ''))) = 'housekeeping' AND department != 'Housekeeping'");
            DB::statement("UPDATE service_requests SET department = 'Front Desk' WHERE LOWER(TRIM(COALESCE(department, ''))) = 'front desk' AND department != 'Front Desk'");
        }

        if (Schema::hasTable('ai_triage_results')) {
            DB::statement("UPDATE ai_triage_results SET suggested_priority = LOWER(TRIM(suggested_priority)) WHERE suggested_priority IS NOT NULL");
            DB::statement("UPDATE ai_triage_results SET suggested_department = 'Maintenance' WHERE LOWER(TRIM(COALESCE(suggested_department, ''))) = 'engineering'");
        }

        // Drop duplicate SQLite indexes created across 2026_09_22_000001 and 2026_11_23_000003
        try {
            DB::statement('DROP INDEX IF EXISTS idx_sr_updated');
            DB::statement('DROP INDEX IF EXISTS idx_tm_sender_read');
        } catch (\Throwable $e) {
            // Safe no-op if index does not exist on non-SQLite drivers
        }
    }

    public function down(): void
    {
        // Canonical data normalization is non-destructive
    }
};
