<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('call_id', 60)->index();
            $table->string('caller_number', 40);
            $table->string('called_number', 40)->nullable();
            $table->string('model', 100);
            $table->string('voice', 50);
            $table->text('prompt');
            $table->integer('duration_sec')->default(0);
            $table->integer('turns')->default(0);
            $table->decimal('cost_estimated', 8, 4)->default(0);
            $table->json('transcript')->nullable();
            $table->string('hangup_reason', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
