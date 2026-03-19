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
        Schema::disableForeignKeyConstraints();

        Schema::create('student_practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained();
            $table->foreignId('practice_id')->constrained();
            $table->foreignId('practice_type_id')->constrained();
            $table->text('scenario');
            $table->dateTime('scheduled_at');
            $table->dateTime('completed_at');
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            $table->foreignId('instructor_id')->constrained('teachers');
            $table->text('result');
            $table->text('observations');
            $table->json('meta');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_practices');
    }
};
