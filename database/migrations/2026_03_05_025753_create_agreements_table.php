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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();              // CONV-2024-000001
            $table->foreignId('student_id')->constrained('students');

            $table->enum('type', ['credit_extension', 'installment_plan', 'both']);

            // Extensión de fecha
            $table->date('original_due_date')->nullable();
            $table->date('new_due_date')->nullable();
            $table->unsignedInteger('extra_days')->nullable();

            // Parcialidades
            $table->unsignedTinyInteger('installments_count')->nullable();
            $table->decimal('installment_amount', 10, 2)->nullable();
            $table->date('first_installment_date')->nullable();

            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);

            $table->enum('status', [
                'pending_approval', 'active', 'completed', 'defaulted', 'cancelled'
            ])->default('pending_approval');

            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
