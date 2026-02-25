<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mt_address', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('street');
            $table->string('province_id');
            $table->string('regency_id');
            $table->string('district_id');
            $table->string('zip_code')->nullable();
            $table->text('notes')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
            $table->foreign('province_id')->references('id')->on('mt_province');
            $table->foreign('regency_id')->references('id')->on('mt_regency');
            $table->foreign('district_id')->references('id')->on('mt_district');
        });

        Schema::table('mt_user', function (Blueprint $table) {
            $table->foreignUuid('address_id')->nullable()->constrained('mt_address')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_address');
    }
};
