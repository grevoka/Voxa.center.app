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

        $inserted = 0;

        foreach ($rows as $row) {
            // Skip if already imported (by uniqueid)
            if (CallLog::where('uniqueid', $row->uniqueid)->exists()) {
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
            $srcName = null;
            if (preg_match('/^"([^"]+)"/', $row->clid, $m)) {
                $srcName = $m[1];
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
                'src'         => $row->src,
                'dst'         => $row->dst,
                'src_name'    => $srcName,
                'context'     => $row->dcontext,
                'channel'     => $row->channel,
                'dst_channel' => $row->dstchannel,
                'direction'   => $direction,
                'trunk_name'  => $trunkName,
                'disposition'  => $row->disposition,
                'duration'    => $row->duration,
                'billsec'     => $row->billsec,
                'started_at'  => $startedAt,
                'answered_at' => $answeredAt,
                'ended_at'    => $endedAt,
                'extra'       => ['cdr_id' => $row->id],
            ]);

            $inserted++;
        }

        if ($inserted > 0) {
            $this->info("Synced {$inserted} CDR records.");
        }

        return self::SUCCESS;
    }
}
