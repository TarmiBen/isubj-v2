<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_response_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('survey_question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('answer_numeric', 5, 2)->nullable();
            $table->text('answer_text')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
