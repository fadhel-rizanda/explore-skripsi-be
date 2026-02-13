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
        Schema::create('mt_attachment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('uploaded_by')->constrained('mt_user')->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->string('public_url')->nullable();
            $table->string('reference_by')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::table('mt_user', function (Blueprint $table) {
            $table->foreignUuid('attachment_id')->nullable()->constrained('mt_attachment')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_attachment');
    }
};
