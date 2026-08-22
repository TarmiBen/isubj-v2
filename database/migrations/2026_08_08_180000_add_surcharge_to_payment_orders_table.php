<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            // Adeudo original del que se derivó este recargo (null en adeudos normales).
            $table->foreignId('parent_payment_order_id')
                  ->nullable()
                  ->after('agreement_id')
                  ->constrained('payment_orders')
                  ->nullOnDelete();

            // Bandera para clasificar/filtrar intereses sin depender del concepto.
            $table->boolean('is_surcharge')->default(false)->after('parent_payment_order_id');

            // % aplicado sobre el total del adeudo original.
            $table->decimal('surcharge_rate', 5, 2)->nullable()->after('is_surcharge');

            $table->index(['is_surcharge', 'status']);
        });

        Schema::table('payment_order_payments', function (Blueprint $table) {
            // Días de atraso de este abono respecto al due_date del adeudo.
            // Se persiste para que el historial y los reportes no cambien si
            // después se edita la fecha de vencimiento.
            $table->unsignedInteger('days_late')->nullable()->after('amount_applied');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropIndex(['is_surcharge', 'status']);
            $table->dropConstrainedForeignId('parent_payment_order_id');
            $table->dropColumn(['is_surcharge', 'surcharge_rate']);
        });

        Schema::table('payment_order_payments', function (Blueprint $table) {
            $table->dropColumn('days_late');
        });
    }
};
