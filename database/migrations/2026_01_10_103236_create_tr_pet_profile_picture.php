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
        Schema::create('tr_pet_profile_picture', function (Blueprint $table) {
            $table->foreignUuid('pet_id')->constrained('tr_pet')->onDelete('cascade');
            $table->foreignUuid('attachment_id')->constrained('mt_attachment')->onDelete('cascade');
            $table->primary(['pet_id', 'attachment_id']);
            $table->index('attachment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_pet_profile_picture');
    }
};
