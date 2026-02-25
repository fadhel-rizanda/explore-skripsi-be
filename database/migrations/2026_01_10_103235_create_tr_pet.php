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
        Schema::create('tr_pet', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('cascade');
            $table->foreignUuid('type_of_animal_id')->constrained('mt_all_tag');
            $table->foreignUuid('address_id')->nullable()->constrained('mt_address')->nullOnDelete();
            $table->string('size', 20);
            $table->string('name', 50);
            $table->date('date_of_birth');
            $table->string('gender', 20);
            $table->text('about');
            $table->string('breed');
            $table->boolean('special_needs')->default(false);
            $table->timestamps();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_pet');
    }
};
