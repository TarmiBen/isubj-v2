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
        Schema::create('extraordinaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qualification_id');
            $table->float('extraordinary_score')->scope(5, 2)->nullable(false);
            $table->date('date_taken');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('observations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraordinaries');
    }
};
