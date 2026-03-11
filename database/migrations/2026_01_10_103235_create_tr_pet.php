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
        Schema::create('tr_pet', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('cascade');
            $table->foreignUuid('type_of_animal_id')->constrained('mt_all_tag');
            $table->string('size', 20)->index();
            $table->string('name', 50)->index();
            $table->date('date_of_birth');
            $table->string('gender', 20)->index();
            $table->text('about');
            $table->string('breed')->index();
            $table->boolean('special_needs')->default(false)->index();
            $table->timestamps();
            $table->boolean('is_active')->default(true)->index();
            $table->index(['type_of_animal_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_pet');
    }
};
