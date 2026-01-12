<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::create('mt_refresh_token', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('mt_user')->cascadeOnDelete();
            $table->string('token', 500)->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['token', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mt_refresh_token');
    }
};
