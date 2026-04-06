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
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained('galleries')->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename');
            $table->unsignedInteger('size'); // en bytes
            $table->unsignedInteger('original_size')->nullable(); // tamaño original antes de comprimir
            $table->string('mime_type');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('gallery_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
    }
};
