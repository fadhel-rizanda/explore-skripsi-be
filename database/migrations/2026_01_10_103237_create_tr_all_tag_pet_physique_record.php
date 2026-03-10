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
        Schema::create('tr_all_tag_pet_physique_record', function (Blueprint $table) {
            $table->foreignUuid('pet_id')->constrained('tr_pet')->onDelete('cascade');
            $table->foreignUuid('all_tag_id')->constrained('mt_all_tag')->onDelete('cascade');
            $table->primary(['pet_id', 'all_tag_id']);
            $table->index('all_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_all_tag_pet_physique_record');
    }
};
