<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name', 150)->nullable();
            $table->string('conference_number', 20)->unique();
            $table->string('pin', 20)->nullable();
            $table->string('admin_pin', 20)->nullable();
            $table->integer('max_members')->default(10);
            $table->string('music_on_hold', 80)->default('default');
            $table->boolean('record')->default(false);
            $table->boolean('mute_on_join')->default(false);
            $table->boolean('announce_join_leave')->default(true);
            $table->boolean('wait_for_leader')->default(false);
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_rooms');
    }
};
