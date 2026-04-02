<?php

namespace Database\Seeders;

use App\Models\CallFlow;
use App\Models\CallQueue;
use App\Models\Trunk;
use Illuminate\Database\Seeder;

class CallFlowSeeder extends Seeder
{
    public function run(): void
    {
        // Recuperer le premier trunk disponible (ou creer un placeholder)
        $trunk = Trunk::first();

        if (!$trunk) {
            $this->command->warn('Aucun trunk trouve — seed CallFlow ignore. Creez un trunk d\'abord.');
            return;
        }

        $contextName = 'from-trunk-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $trunk->name));

        // ── File d'attente "accueil" ──
        $queue = CallQueue::firstOrCreate(
            ['name' => 'accueil'],
            [
                'display_name'       => 'Accueil',
                'strategy'           => 'ringall',
                'timeout'            => 25,
                'retry'              => 5,
                'max_wait_time'      => 120,
                'music_on_hold'      => 'default',
                'announce_frequency' => 0,
                'announce_holdtime'  => 'no',
                'members'            => [
                    ['extension' => '1001', 'penalty' => 0],
                ],
                'enabled'            => true,
            ]
        );

        // ── Scenario : Entrant → Sonnerie → Repondeur ──
        CallFlow::firstOrCreate(
            ['name' => 'accueil-standard'],
            [
                'description'     => 'Appel entrant : sonnerie sur poste 1001, puis repondeur si pas de reponse',
                'trunk_id'        => $trunk->id,
                'inbound_context' => $contextName,
                'enabled'         => true,
                'priority'        => 10,
                'steps'           => [
                    [
                        'type' => 'answer',
                        'wait' => 1,
                    ],
                    [
                        'type'       => 'ring',
                        'extensions' => ['1001'],
                        'timeout'    => 25,
                    ],
                    [
                        'type'    => 'voicemail',
                        'mailbox' => '1001',
                        'vm_type' => 'u',
                    ],
                    [
                        'type' => 'hangup',
                    ],
                ],
            ]
        );

        $this->command->info("Seed CallFlow: accueil-standard ({$contextName}) + queue accueil crees.");
    }
}
