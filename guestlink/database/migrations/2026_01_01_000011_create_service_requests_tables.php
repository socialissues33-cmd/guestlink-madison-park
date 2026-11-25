<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // service, maintenance
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('request_categories')->nullOnDelete();
            $table->string('type'); // service, maintenance
            $table->string('guest_name')->nullable();
            $table->text('description');
            $table->string('status')->default('pending'); // pending, assigned, in_progress, resolved, closed
            $table->string('priority')->nullable(); // low, medium, high, urgent
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department')->nullable(); // Front Desk, Maintenance, Housekeeping
            $table->string('reference_number')->unique();
            $table->timestamps();
        });

        Schema::create('ai_triage_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
            $table->string('suggested_category')->nullable();
            $table->string('suggested_priority')->nullable();
            $table->string('suggested_department')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending (unreviewed), accepted, modified
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_logs');
        Schema::dropIfExists('ai_triage_results');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('request_categories');
    }
};
