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
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()')); // sebenernya kg eprlu lg default soalnya udh pake boot
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('public_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
