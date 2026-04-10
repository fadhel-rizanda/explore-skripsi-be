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
        Schema::dropIfExists('tr_community_admin');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tr_community_admin', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('community_id')->constrained('mt_community')->onDelete('cascade');
            $table->primary(['user_id', 'community_id']);
        });
    }
};
