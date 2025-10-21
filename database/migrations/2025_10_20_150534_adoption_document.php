<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('adoptions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()')); // sebenernya kg eprlu lg default soalnya udh pake boot
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('adoption_documents', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()')); // sebenernya kg eprlu lg default soalnya udh pake boot
            $table->foreignUuid('adoption_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['adoption_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('adoption_documents');
    }
};
