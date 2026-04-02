<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->foreignId('trunk_id')->constrained('trunks')->cascadeOnDelete();
            $table->string('inbound_context', 50);
            $table->json('steps');
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(50);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('call_queues', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name', 150)->nullable();
            $table->string('strategy', 30)->default('ringall');
            $table->integer('timeout')->default(30);
            $table->integer('retry')->default(5);
            $table->integer('max_wait_time')->default(300);
            $table->string('music_on_hold', 80)->default('default');
            $table->string('announce_frequency', 10)->nullable();
            $table->string('announce_holdtime', 3)->default('yes');
            $table->json('members')->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_queues');
        Schema::dropIfExists('call_flows');
    }
};
