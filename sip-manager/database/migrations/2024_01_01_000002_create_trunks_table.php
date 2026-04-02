<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trunks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->enum('type', ['SIP', 'IAX', 'PRI'])->default('SIP');
            $table->enum('transport', ['UDP', 'TCP', 'TLS'])->default('UDP');
            $table->string('host', 255);
            $table->unsignedInteger('port')->default(5060);
            $table->string('username', 100)->nullable();
            $table->text('secret')->nullable();
            $table->unsignedInteger('max_channels')->default(30);
            $table->json('codecs')->nullable();
            $table->string('caller_id', 50)->nullable();
            $table->string('context', 50)->default('from-trunk');
            $table->enum('status', ['online', 'offline', 'error'])->default('offline');
            $table->boolean('register')->default(true);
            $table->unsignedInteger('retry_interval')->default(60);
            $table->unsignedInteger('expiration')->default(3600);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trunks');
    }
};
