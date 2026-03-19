<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_fee_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_concept_id')->constrained('payment_concepts');

            // A qué generación aplica (null = todas)
            $table->foreignId('generation_id')->nullable()->constrained('generations');

            $table->decimal('amount', 10, 2);

            // Día del mes en que se genera la mensualidad (1-28)
            $table->unsignedTinyInteger('generation_day')->default(1);

            // Días de vencimiento después de generación
            $table->unsignedInteger('due_days')->default(10);

            // Meses del ciclo que cubre esta config (ej: 1=agosto, 2=septiembre...)
            $table->unsignedTinyInteger('months_count')->default(10);

            // Mes y año desde cuando aplica
            $table->unsignedTinyInteger('start_month');   // 1-12
            $table->unsignedSmallInteger('start_year');

            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['generation_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fee_configs');
    }
};