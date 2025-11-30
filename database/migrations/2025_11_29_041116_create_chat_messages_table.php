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
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->string('name')->nullable();
            $table->enum('type', ['private', 'group'])->default('private');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('chat_room_user', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->foreignUuid('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['chat_room_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->foreignUuid('room_id')->constrained('chat_rooms')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->foreignUuid('attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'created_at']);
        });

        Schema::create('chat_message_reads', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->foreignUuid('message_id')->constrained('chat_messages')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_reads');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_room_user');
        Schema::dropIfExists('chat_rooms');
    }
};
