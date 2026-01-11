<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
            $table->uuid('type_of_animal_id');
            $table->uuid('size_id');
            $table->string('name', 50);
            $table->date('date_of_birth');
            $table->uuid('gender_id');
            $table->text('about');
            $table->string('breed');
            $table->string('profile_picture');
            $table->boolean('special_needs')->default(false);
            $table->timestamps();
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
