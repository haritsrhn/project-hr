<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_month');     // 1–12
            $table->unsignedSmallInteger('period_year');
            $table->enum('status', ['DRAFT', 'PROCESSED', 'PAID'])->default('DRAFT');
            $table->unsignedBigInteger('total_gross')->default(0);
            $table->unsignedBigInteger('total_net')->default(0);
            $table->unsignedInteger('total_employees')->default(0);
            $table->uuid('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->uuid('locked_by')->nullable();          // siapa yang finalisasi
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'period_month', 'period_year']);
            $table->index('status');
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employment_id')->constrained()->cascadeOnDelete();

            // Gaji Pokok + Tunjangan
            $table->unsignedBigInteger('gross_salary');
            $table->json('allowances')->nullable();         // snapshot tunjangan aktual bulan ini

            // Iuran BPJS (disimpan sebagai rupiah — snapshot saat diproses)
            $table->unsignedBigInteger('bpjs_kes_employee')->default(0);    // 1% dari karyawan
            $table->unsignedBigInteger('bpjs_kes_employer')->default(0);    // 4% dari perusahaan
            $table->unsignedBigInteger('bpjs_jht_employee')->default(0);    // 2%
            $table->unsignedBigInteger('bpjs_jht_employer')->default(0);    // 3.7%
            $table->unsignedBigInteger('bpjs_jkk')->default(0);             // employer only (0.24–1.74%)
            $table->unsignedBigInteger('bpjs_jkm')->default(0);             // employer only (0.3%)
            $table->unsignedBigInteger('bpjs_jp_employee')->default(0);     // 1%
            $table->unsignedBigInteger('bpjs_jp_employer')->default(0);     // 2%

            // PPh 21
            $table->unsignedBigInteger('pph21_annual_base')->default(0);    // dasar pengenaan pajak tahunan
            $table->unsignedBigInteger('pph21_amount')->default(0);         // potongan PPh 21 bulan ini
            $table->json('pph21_breakdown')->nullable();                    // rincian kalkulasi progresif

            // Potongan lain & Gaji Bersih
            $table->json('deductions')->nullable();         // potongan insidentil bulan ini
            $table->unsignedBigInteger('net_salary');

            // Kehadiran (snapshot untuk referensi)
            $table->unsignedInteger('working_days');
            $table->unsignedInteger('present_days');
            $table->unsignedInteger('absent_days')->default(0);
            $table->unsignedInteger('leave_days')->default(0);

            $table->string('slip_url')->nullable();         // URL PDF slip gaji
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employment_id']);
            $table->index('employment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
    }
};
