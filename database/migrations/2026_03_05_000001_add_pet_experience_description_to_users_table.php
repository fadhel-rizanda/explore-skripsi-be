<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mt_user', function (Blueprint $table) {
            $table->text('pet_experience_description')->nullable()->after('pet_experience');
        });
    }

    public function down(): void
    {
        Schema::table('mt_user', function (Blueprint $table) {
            $table->dropColumn('pet_experience_description');
        });
    }
};
