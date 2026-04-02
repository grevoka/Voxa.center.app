<?php

namespace App\Jobs;

use App\Services\AsteriskAmiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReloadAsteriskConfig implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function handle(AsteriskAmiService $ami): void
    {
        $success = $ami->pjsipReload();

        if (!$success) {
            Log::warning('ReloadAsteriskConfig: reload failed, will retry');
            $this->release(10);
        }
    }
}
