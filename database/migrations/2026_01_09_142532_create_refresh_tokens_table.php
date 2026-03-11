<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create('mt_refresh_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('mt_user')->cascadeOnDelete();
            $table->string('token', 500)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['token', 'expires_at', 'used_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mt_refresh_token');
    }
};
