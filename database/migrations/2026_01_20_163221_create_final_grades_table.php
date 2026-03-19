<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assignment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('attempt')
                ->comment('1=ordinario, 2=extraordinario, 3=titulo');

            $table->decimal('grade', 4, 1);

            $table->enum('status', ['passed', 'failed']);

            $table->enum('source', ['ordinario', 'extraordinario', 'especial']);

            $table->json('calculated_from')->nullable()
                ->comment('Parciales usados para el cálculo');

            $table->timestamps();

            // Evita duplicar el mismo intento
            $table->unique(['student_id', 'assignment_id', 'attempt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
    }
};

