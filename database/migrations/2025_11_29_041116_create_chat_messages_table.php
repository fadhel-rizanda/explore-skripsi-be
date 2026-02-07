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
        Schema::create('mt_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['private', 'public'])->default('private');
            $table->foreignUuid('created_by')->nullable()->constrained('mt_user')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('mt_user')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('tr_chat_room', function (Blueprint $table) {
            $table->foreignUuid('chat_id')->constrained('mt_chat')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->primary(['chat_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('tr_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_id')->constrained('mt_chat')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->foreignUuid('attachment_id')->nullable()->constrained('mt_attachment')->nullOnDelete();
            $table->timestamps();

            $table->index(['chat_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_message');
        Schema::dropIfExists('tr_chat_room');
        Schema::dropIfExists('mt_chat');
    }
};
