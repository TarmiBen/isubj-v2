<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_related', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->morphs('survivable');

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_assignments');
    }
};
