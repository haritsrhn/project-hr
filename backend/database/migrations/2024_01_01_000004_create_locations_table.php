<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->string('qr_code_token')->unique();  // rotated time-based token
            $table->timestamp('qr_rotated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('entity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
