<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('mt_user', function (Blueprint $table) {
            $table->dropIndex(['name']);

            $table->unique('name');

            $table->boolean('open_to_special_needs')
                ->default(true)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('mt_user', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->index('name');

            $table->boolean('open_to_special_needs')
                ->default(false)
                ->change();
        });
    }
};
