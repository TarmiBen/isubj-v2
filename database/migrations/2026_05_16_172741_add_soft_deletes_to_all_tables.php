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
        // Lista de tablas que necesitan la columna deleted_at
        $tables = [
            'users',
            'careers',
            'periods',
            'generations',
            'cycles',
            'durations',
            'documents',
            'inscriptions',
            'agendas',
            'alerts',
            'qualifications',
            'units',
            'evaluations',
            'posts',
            'surveys',
            'quizzes',
            'reservations',
            'galleries',
            'questions',
            'question_options',
            'survey_questions',
            'survey_relateds',
            'survey_responses',
            'survey_answers',
            'final_grades',
            'referrals',
            'discount_applications',
            'agreement_installments',
            'gallery_photos',
            'correspondences',
            'services',
            'practices',
            'practice_types',
            'student_practices',
            'payment_methods',
            'payment_order_payments',
            'payment_references',
            'lows',
            'charge_discounts',
            'charges',
            'discounts',
            'payments',
            'payments_incomes',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lista de tablas para remover la columna deleted_at
        $tables = [
            'users',
            'careers',
            'periods',
            'generations',
            'cycles',
            'durations',
            'documents',
            'inscriptions',
            'agendas',
            'alerts',
            'qualifications',
            'units',
            'evaluations',
            'posts',
            'surveys',
            'quizzes',
            'reservations',
            'galleries',
            'questions',
            'question_options',
            'survey_questions',
            'survey_relateds',
            'survey_responses',
            'survey_answers',
            'final_grades',
            'referrals',
            'discount_applications',
            'agreement_installments',
            'gallery_photos',
            'correspondences',
            'services',
            'practices',
            'practice_types',
            'student_practices',
            'payment_methods',
            'payment_order_payments',
            'payment_references',
            'lows',
            'charge_discounts',
            'charges',
            'discounts',
            'payments',
            'payments_incomes',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
