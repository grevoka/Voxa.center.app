<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sip_lines', function (Blueprint $table) {
            $table->id();
            $table->string('extension', 20)->unique();
            $table->string('name', 100);
            $table->string('email', 255)->nullable();
            $table->text('secret');
            $table->enum('protocol', ['SIP/UDP', 'SIP/TCP', 'SIP/TLS', 'WebRTC'])
                  ->default('SIP/UDP');
            $table->string('caller_id', 50)->nullable();
            $table->string('context', 50)->default('from-internal');
            $table->json('codecs')->nullable();
            $table->enum('status', ['online', 'offline', 'busy'])->default('offline');
            $table->string('transport', 50)->default('transport-udp');
            $table->integer('max_contacts')->default(1);
            $table->boolean('voicemail_enabled')->default(false);
            $table->string('voicemail_email')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sip_lines');
    }
};
