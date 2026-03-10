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
        Schema::create('mt_report', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_type')->index();
            $table->uuid('reference_id')->index();
            $table->text('notes')->nullable();
            $table->foreignUuid('status_id')->constrained('mt_all_status')->onDelete('cascade')->index();
            $table->foreignUuid('created_by')->constrained('mt_user')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent()->index(); // INDEX: Untuk mengurutkan laporan terbaru
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('tr_all_tag_report_record', function (Blueprint $table) {
            $table->foreignUuid('report_id')->constrained('mt_report')->onDelete('cascade');
            $table->foreignUuid('all_tag_id')->constrained('mt_all_tag')->onDelete('cascade');
            $table->primary(['report_id', 'all_tag_id']);
            $table->index('all_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_all_tag_report_record');
        Schema::dropIfExists('mt_report');
    }
};
