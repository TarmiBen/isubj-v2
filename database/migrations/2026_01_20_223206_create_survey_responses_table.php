<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_related_id')
                ->constrained('survey_related')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('cycle_id')
                ->constrained('cycles')
                ->cascadeOnDelete();

            $table->integer('progress')->default(0);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['survey_related_id', 'student_id', 'cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
