<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_files', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('original_name', 255);
            $table->string('filename', 255)->unique();
            $table->enum('category', ['sound', 'moh'])->default('sound');
            $table->string('moh_class', 50)->nullable();
            $table->integer('duration')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('format', 20)->default('wav');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_files');
    }
};
