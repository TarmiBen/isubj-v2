<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modality_id');
            $table->unsignedBigInteger('coordinator_id');
            $table->unsignedBigInteger('duration_id');
            $table->integer('duration_time');
            $table->string('code')->unique();
            $table->string('name', 100);
            $table->string('abbreviation', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->integer('total_credits');
            $table->enum('status', ["active", "inactive"])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
