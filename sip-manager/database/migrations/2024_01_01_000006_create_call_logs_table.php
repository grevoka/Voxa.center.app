<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uniqueid', 150)->index();       // Asterisk UNIQUEID
            $table->string('src', 80)->nullable();           // source (caller)
            $table->string('dst', 80)->nullable();           // destination (callee)
            $table->string('src_name')->nullable();          // caller name
            $table->string('context', 80)->nullable();       // dialplan context used
            $table->string('channel', 200)->nullable();      // PJSIP/line-1001-000001
            $table->string('dst_channel', 200)->nullable();  // PJSIP/line-1002-000002
            $table->enum('direction', ['inbound', 'outbound', 'internal'])->default('internal');
            $table->string('trunk_name')->nullable();        // trunk used (if any)
            $table->enum('disposition', ['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED', 'CONGESTION'])->default('NO ANSWER');
            $table->integer('duration')->default(0);         // total duration (seconds)
            $table->integer('billsec')->default(0);          // billable seconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('recording_file')->nullable();    // path to recording
            $table->string('hangup_cause')->nullable();
            $table->json('extra')->nullable();               // additional AMI data
            $table->timestamps();

            $table->index('src');
            $table->index('dst');
            $table->index('started_at');
            $table->index('disposition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
