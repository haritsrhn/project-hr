<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 20)->unique();
            $table->string('position');
            $table->string('department');
            $table->enum('employment_type', ['PERMANENT', 'CONTRACT', 'INTERN']);
            $table->unsignedBigInteger('salary_basic');
            $table->json('salary_structure')->nullable();    // komponen tunjangan/potongan tetap
            $table->enum('ptkp_status', ['TK0','TK1','TK2','TK3','K0','K1','K2','K3'])->default('TK0');
            $table->boolean('bpjs_kesehatan')->default(true);
            $table->boolean('bpjs_tk')->default(true);
            $table->date('join_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_primary')->default(false);  // entitas utama karyawan
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'TERMINATED'])->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('entity_id');
            $table->index('status');
            $table->unique(['user_id', 'entity_id', 'status']);  // satu user satu record aktif per entitas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
