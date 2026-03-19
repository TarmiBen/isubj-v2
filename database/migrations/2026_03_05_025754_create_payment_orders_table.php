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
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();              // PO-2024-000001
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('payment_concept_id')->constrained('payment_concepts');

            // POLIMÓRFICO — puede venir de monthly_fees, inscriptions, constancy_requests, etc.
            $table->nullableMorphs('chargeable');           // chargeable_type + chargeable_id

            $table->date('period_start')->nullable();       // para mensualidades
            $table->date('period_end')->nullable();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);  // se actualiza con cada abono
            $table->decimal('balance', 10, 2);

            $table->date('due_date');
            $table->date('paid_at')->nullable();

            $table->enum('status', [
                'pending', 'partial', 'paid', 'overdue', 'cancelled', 'in_agreement'
            ])->default('pending');

            $table->foreignId('agreement_id')->nullable()->constrained('agreements');

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status']);
            $table->index(['student_id', 'due_date']);
            $table->index(['status', 'due_date']);          // para estados de cuenta masivos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
