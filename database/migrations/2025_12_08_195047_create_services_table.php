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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100);
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('price');
            $table->boolean('status')->default(true);
            $table->boolean('require_assignment')->default(false);
            $table->boolean('require_period')->default(false);
            $table->integer('days_period')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->integer('recurring_interval')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
