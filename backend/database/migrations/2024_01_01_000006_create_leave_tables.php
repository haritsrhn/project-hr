<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('max_days_per_year');
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_over')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('entity_id');
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_days');
            $table->unsignedInteger('used_days')->default(0);
            $table->timestamps();

            $table->unique(['employment_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_days');
            $table->text('reason');
            $table->string('attachment_url')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('PENDING');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employment_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
