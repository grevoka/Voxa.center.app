<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCdr extends Command
{
    protected $signature = 'cdr:sync';
    protected $description = 'Sync Asterisk CDR records into call_logs table';

    public function handle(): int
    {
        $lastId = (int) CallLog::max('extra->cdr_id') ?: 0;

        $rows = DB::connection('asterisk')
            ->table('cdr')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit(500)
            ->get();

        if ($rows->isEmpty()) {
            return self::SUCCESS;
        }

        // Group by uniqueid — keep the last record (final state) per call
        $grouped = $rows->groupBy('uniqueid')->map(function ($group) {
            return $group->last();
        });

        $inserted = 0;

        foreach ($grouped as $uniqueid => $row) {
            // Update max cdr_id tracking even if we skip
            $maxId = $rows->where('uniqueid', $uniqueid)->max('id');

            // Skip if already imported
            if (CallLog::where('uniqueid', $uniqueid)->exists()) {
                // Update cdr_id to latest so we don't re-process
                CallLog::where('uniqueid', $uniqueid)
                    ->update(['extra->cdr_id' => $maxId]);
                continue;
            }

            // Determine direction from context
            $direction = 'internal';
            if (str_starts_with($row->dcontext, 'from-trunk') || str_starts_with($row->dcontext, 'from-pstn')) {
                $direction = 'inbound';
            } elseif (str_starts_with($row->dcontext, 'to-trunk') || str_starts_with($row->dcontext, 'to-pstn') || str_starts_with($row->dcontext, 'outbound')) {
                $direction = 'outbound';
            }

            // Extract trunk name from context (e.g. "from-trunk-ovh-in" → "ovh")
            $trunkName = null;
            if (preg_match('/^(?:from|to)-trunk-([^-]+)/', $row->dcontext, $m)) {
                $trunkName = $m[1];
            }

            // Extract caller name from CLID: "Name" <number>
            // Only store as name if it's not just a phone number
            $srcName = null;
            if (preg_match('/^"([^"]+)"/', $row->clid, $m)) {
                $name = trim($m[1]);
                // Don't store if it's just a number (local or international format)
                if ($name !== '' && !preg_match('/^\+?\d+$/', $name)) {
                    $srcName = $name;
                }
            }

            // Use src from CDR, but if empty try to extract from CLID
            $src = $row->src;
            if (empty($src) && preg_match('/<([^>]+)>/', $row->clid, $m)) {
                $src = $m[1];
            }

            $startedAt = $row->calldate;
            $endedAt = $startedAt
                ? date('Y-m-d H:i:s', strtotime($startedAt) + ($row->duration ?? 0))
                : null;
            $answeredAt = ($row->billsec > 0 && $startedAt)
                ? date('Y-m-d H:i:s', strtotime($endedAt) - $row->billsec)
                : null;

            CallLog::create([
                'uniqueid'    => $row->uniqueid,
                'src'         => $src,
                'dst'         => $row->dst,
                'src_name'    => $srcName,
                'context'     => $row->dcontext,
                'channel'     => $row->channel,
                'dst_channel' => $row->dstchannel,
                'direction'   => $direction,
                'trunk_name'  => $trunkName,
                'disposition' => $row->disposition,
                'duration'    => $row->duration,
                'billsec'     => $row->billsec,
                'started_at'  => $startedAt,
                'answered_at' => $answeredAt,
                'ended_at'    => $endedAt,
                'extra'       => ['cdr_id' => $maxId],
            ]);

            $inserted++;
        }

        if ($inserted > 0) {
            $this->info("Synced {$inserted} CDR records.");
        }

        return self::SUCCESS;
    }
}
