<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('token', 64)->unique();
            $table->string('domain', 255);
            $table->foreignId('callflow_id')->nullable()->constrained('call_flows')->nullOnDelete();
            $table->string('extension', 20)->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('max_concurrent')->default(5);
            $table->unsignedBigInteger('call_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_tokens');
    }
};
