<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mt_adoption_application', function (Blueprint $table) {
            $table->dropUnique('mt_adoption_application_pet_id_unique');
        });

        DB::statement('CREATE UNIQUE INDEX unique_active_pet_application ON mt_adoption_application (pet_id) WHERE (is_active = true)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_pet_application');
        Schema::table('mt_adoption_application', function (Blueprint $table) {
            $table->unique('pet_id');
        });
    }
};
