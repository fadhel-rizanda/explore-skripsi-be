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
        Schema::create('mt_post', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->text('content');
            $table->foreignUuid('community_id')->nullable()->constrained('mt_community')->onDelete('set null');
            $table->foreignUuid('attachment_id')->nullable()->constrained('mt_attachment')->onDelete('set null');
            $table->foreignUuid('created_by')->nullable()->constrained('mt_user')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('tr_like_post', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('post_id')->constrained('mt_post')->onDelete('cascade');
            $table->primary(['user_id', 'post_id']);
        });

        Schema::create('tr_tag_post_record', function (Blueprint $table) {
            $table->foreignUuid('tag_id')->constrained('mt_all_tag')->onDelete('cascade');
            $table->foreignUuid('post_id')->constrained('mt_post')->onDelete('cascade');
            $table->primary(['tag_id', 'post_id']);
        });

        Schema::create('mt_comment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('content');
            $table->foreignUuid('post_id')->constrained('mt_post')->onDelete('cascade');
            $table->foreignUuid('created_by')->nullable()->constrained('mt_user')->onDelete('set null');
            $table->uuid('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::table('mt_comment', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('mt_comment')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_comment');
        Schema::dropIfExists('tr_tag_post_record');
        Schema::dropIfExists('tr_like_post');
        Schema::dropIfExists('mt_post');
    }
};
