<?php

namespace App\Jobs;

use App\Models\SipLine;
use App\Models\Trunk;
use App\Services\AsteriskAmiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SyncPeerStatuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(AsteriskAmiService $ami): void
    {
        SipLine::each(function (SipLine $line) use ($ami) {
            $status = $ami->getEndpointStatus($line->getAsteriskEndpointId());
            $line->updateQuietly(['status' => $status['available'] ? 'online' : 'offline']);
        });

        Trunk::each(function (Trunk $trunk) use ($ami) {
            $status = $ami->getTrunkRegistrationStatus($trunk->getAsteriskEndpointId() . '-reg');
            $trunk->updateQuietly(['status' => $status]);
        });
    }
}
