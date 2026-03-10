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
        Schema::create('tr_notification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message');
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->uuid('reference_id')->nullable()->index();
            $table->string('reference_type')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();

            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_notification');
    }
};
