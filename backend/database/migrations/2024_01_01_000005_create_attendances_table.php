<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employment_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->enum('method', ['GPS', 'QR', 'MANUAL'])->default('GPS');
            $table->decimal('lat_in', 10, 7)->nullable();
            $table->decimal('lng_in', 10, 7)->nullable();
            $table->decimal('lat_out', 10, 7)->nullable();
            $table->decimal('lng_out', 10, 7)->nullable();
            $table->string('device_hash')->nullable();      // fingerprint perangkat
            $table->foreignUuid('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('status', ['PRESENT', 'LATE', 'ABSENT', 'LEAVE'])->default('PRESENT');
            $table->text('notes')->nullable();              // catatan koreksi manual
            $table->uuid('corrected_by')->nullable();       // HRD yang melakukan koreksi
            $table->timestamps();

            $table->unique(['employment_id', 'date']);      // satu record per hari per employment
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
