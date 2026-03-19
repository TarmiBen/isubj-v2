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
        Schema::create('payment_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();           // MENS, INSCR, CONST, SEG, CRED, PRAC
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'mensualidad', 'inscripcion', 'constancia',
                'seguro', 'credencial', 'practica', 'otro'
            ]);
            $table->decimal('default_amount', 10, 2)->default(0);
            $table->boolean('is_periodic')->default(false);  // genera cargo automático
            $table->enum('period_type', ['mensual','bimestral','semestral','anual'])->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_concepts');
    }
};
