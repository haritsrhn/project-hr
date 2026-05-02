<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['HOLDING', 'PT', 'CV', 'YAYASAN']);
            $table->string('npwp', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account', 30)->nullable();
            $table->string('bank_holder_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('entities')->nullOnDelete();
            $table->index('parent_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
