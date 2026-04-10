<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caller_ids', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40);
            $table->string('label', 100);
            $table->foreignId('trunk_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('caller_id_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('caller_id_group_items', function (Blueprint $table) {
            $table->foreignId('caller_id_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('caller_id_id')->constrained()->cascadeOnDelete();
            $table->primary(['caller_id_group_id', 'caller_id_id']);
        });

        Schema::create('caller_id_group_users', function (Blueprint $table) {
            $table->foreignId('caller_id_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['caller_id_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caller_id_group_users');
        Schema::dropIfExists('caller_id_group_items');
        Schema::dropIfExists('caller_id_groups');
        Schema::dropIfExists('caller_ids');
    }
};
