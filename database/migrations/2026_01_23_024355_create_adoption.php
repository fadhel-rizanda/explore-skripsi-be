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
        Schema::create('mt_schedule', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('scheduled_time');
            $table->foreignUuid('address_id')->nullable()->constrained('mt_address')->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('updated_by')->nullable()->constrained('mt_user')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('mt_adoption_application', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('adopter_id')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('pet_id')->unique()->constrained('tr_pet')->onDelete('cascade');
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('restrict'); // data mt gabisa dihapus
            $table->foreignUuid('stage_tag_id')->constrained('mt_all_tag')->onDelete('restrict');
            $table->foreignUuid('updated_by')->nullable()->constrained('mt_user')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('tr_adoption_meet_greet', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('adoption_id')->constrained('mt_adoption_application')->onDelete('cascade');
            $table->foreignUuid('schedule_id')->constrained('mt_schedule')->onDelete('restrict');
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('restrict');
            $table->boolean('adopter_confirmed')->default(false);
            $table->timestamp('adopter_confirmed_at')->nullable();
            $table->boolean('provider_confirmed')->default(false);
            $table->timestamp('provider_confirmed_at')->nullable();
            $table->foreignUuid('created_by')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('updated_by')->nullable()->constrained('mt_user')->onDelete('cascade');
            $table->string('stage')->default('default'); // default/handover
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tr_adoption_requirement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->foreignUuid('adoption_id')->constrained('mt_adoption_application')->onDelete('cascade');
            $table->foreignUuid('attachment_id')->nullable()->constrained('mt_attachment')->onDelete('cascade');
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('restrict');
            $table->foreignUuid('created_by')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('updated_by')->constrained('mt_user')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('tr_adoption_handover', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('adoption_id')->constrained('mt_adoption_application')->onDelete('cascade');
            $table->foreignUuid('meet_n_greet_id')->constrained('tr_adoption_meet_greet')->restrictOnDelete();
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('restrict');

            $table->boolean('adopter_finalized')->default(false);
            $table->timestamp('adopter_finalized_at')->nullable();
            $table->boolean('provider_finalized')->default(false);
            $table->timestamp('provider_finalized_at')->nullable();
            $table->boolean('admin_finalized')->default(false);
            $table->timestamp('admin_finalized_at')->nullable();

            $table->foreignUuid('created_by')->constrained('mt_user')->onDelete('cascade');
            $table->foreignUuid('updated_by')->nullable()->constrained('mt_user')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tr_adoption_handover_attachment', function (Blueprint $table) {
            $table->foreignUuid('handover_id')->constrained('tr_adoption_handover')->onDelete('cascade');
            $table->foreignUuid('attachment_id')->constrained('mt_attachment')->onDelete('cascade');
            $table->string('uploaded_by_role')->nullable(); // adopter/provider
            $table->primary(['handover_id', 'attachment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_adoption_handover_attachment');
        Schema::dropIfExists('tr_adoption_handover');
        Schema::dropIfExists('tr_adoption_requirement');
        Schema::dropIfExists('mt_adoption_application');
        Schema::dropIfExists('mt_schedule');
    }
};
