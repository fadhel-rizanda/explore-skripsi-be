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
        Schema::create('tr_all_tag_user_personality_record', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('tag_id')->constrained('mt_all_tag')->onDelete('cascade');
            $table->primary(['user_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('tr_all_tag_user_experience_record', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('tag_id')->constrained('mt_all_tag')->onDelete('cascade');
            $table->primary(['user_id', 'tag_id']);
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_all_tag_user_personality_record');
        Schema::dropIfExists('tr_all_tag_user_experience_record');
    }
};
