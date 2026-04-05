<?php

namespace Database\Seeders;

use App\Models\CallFlowTemplate;
use Illuminate\Database\Seeder;

class CallFlowTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'        => 'Accueil standard',
                'description' => 'Decroche, sonne les postes, puis messagerie si pas de reponse',
                'icon'        => 'bi-building',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'playback', 'sound' => 'custom/welcome'],
                    ['type' => 'ring', 'extensions' => [], 'timeout' => 25],
                    ['type' => 'voicemail', 'mailbox' => '1000', 'vm_type' => 'u'],
                    ['type' => 'hangup'],
                ],
            ],
            [
                'name'        => 'Repondeur simple',
                'description' => 'Decroche et envoie directement en messagerie vocale',
                'icon'        => 'bi-voicemail',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'announcement', 'sound' => 'custom/closed'],
                    ['type' => 'voicemail', 'mailbox' => '1000', 'vm_type' => 'u'],
                    ['type' => 'hangup'],
                ],
            ],
            [
                'name'        => 'File d\'attente',
                'description' => 'Decroche, musique d\'attente, puis distribue via une file',
                'icon'        => 'bi-people',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'playback', 'sound' => 'custom/welcome'],
                    ['type' => 'queue', 'queue_name' => 'support', 'timeout' => 120],
                    ['type' => 'voicemail', 'mailbox' => '1000', 'vm_type' => 'u'],
                    ['type' => 'hangup'],
                ],
            ],
            [
                'name'        => 'Sonnerie cascade',
                'description' => 'Sonne un premier poste, puis un second si pas de reponse',
                'icon'        => 'bi-arrow-down-circle',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'ring', 'extensions' => [], 'timeout' => 15],
                    ['type' => 'ring', 'extensions' => [], 'timeout' => 20],
                    ['type' => 'voicemail', 'mailbox' => '1000', 'vm_type' => 'u'],
                    ['type' => 'hangup'],
                ],
            ],
            [
                'name'        => 'Annonce + raccrocher',
                'description' => 'Joue un message d\'annonce puis raccroche (hors service, etc.)',
                'icon'        => 'bi-megaphone',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'announcement', 'sound' => 'custom/closed'],
                    ['type' => 'hangup'],
                ],
            ],
        ];

        foreach ($templates as $t) {
            CallFlowTemplate::updateOrCreate(
                ['name' => $t['name'], 'is_system' => true],
                array_merge($t, ['is_system' => true]),
            );
        }
    }
}
