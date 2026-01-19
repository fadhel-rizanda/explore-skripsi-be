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
        Schema::create('mt_community', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->foreignUuid('attachment_id')->nullable()->constrained('mt_attachment')->onDelete('set null');
            $table->foreignUuid('address_id')->nullable()->constrained('mt_address')->onDelete('set null');
            $table->foreignUuid('created_by')->nullable()->constrained('mt_user')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('tr_community_admin', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('community_id')->constrained('mt_community')->onDelete('cascade');
            $table->primary(['user_id', 'community_id']);
        });

        Schema::create('tr_follow_community', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('community_id')->constrained('mt_community')->onDelete('cascade');
            $table->primary(['user_id', 'community_id']);
        });

        Schema::create('tr_tag_community_record', function (Blueprint $table) {
            $table->foreignUuid('tag_id')->constrained('mt_all_tag')->onDelete('cascade');
            $table->foreignUuid('community_id')->constrained('mt_community')->onDelete('cascade');
            $table->primary(['tag_id', 'community_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_tag_community_record');
        Schema::dropIfExists('tr_follow_community');
        Schema::dropIfExists('tr_community_admin');
        Schema::dropIfExists('mt_community');
    }
};
