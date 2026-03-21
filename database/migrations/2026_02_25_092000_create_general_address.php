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
        Schema::create('mt_province', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('name')->index();
        });

        Schema::create('mt_regency', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('province_id', 10);
            $table->string('name')->index();
            $table->foreign('province_id')->references('id')->on('mt_province')->cascadeOnDelete();

            $table->index('province_id');
        });

        Schema::create('mt_district', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('regency_id', 10);
            $table->string('name');
            $table->foreign('regency_id')->references('id')->on('mt_regency')->cascadeOnDelete();

            $table->index('regency_id');
        });

        Schema::table('mt_address', function (Blueprint $table) {
            $table->foreign('province_id')->references('id')->on('mt_province');
            $table->foreign('regency_id')->references('id')->on('mt_regency');
            $table->foreign('district_id')->references('id')->on('mt_district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_district');
        Schema::dropIfExists('mt_regency');
        Schema::dropIfExists('mt_province');
    }
};
