<?php

namespace App\Console\Commands;

use App\Services\WidgetGuestService;
use Illuminate\Console\Command;

class CleanupWidgetGuests extends Command
{
    protected $signature = 'widget:cleanup';
    protected $description = 'Remove stale widget guest WebRTC endpoints';

    public function handle(WidgetGuestService $service): int
    {
        $count = $service->cleanupStaleGuests();

        if ($count > 0) {
            $this->info("Cleaned up {$count} stale widget guest endpoints.");
        }

        return self::SUCCESS;
    }
}
