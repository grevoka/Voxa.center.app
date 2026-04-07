<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ip_range', 45);
            $table->string('label', 100)->nullable();
            $table->enum('type', ['whitelist', 'blacklist'])->default('whitelist');
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['ip_range', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_rules');
    }
};
