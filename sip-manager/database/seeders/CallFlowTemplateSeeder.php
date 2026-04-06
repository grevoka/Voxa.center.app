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
                'description' => 'Decroche, met en file d\'attente, puis messagerie si pas de reponse',
                'icon'        => 'bi-building',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'playback', 'sound' => 'custom/welcome'],
                    ['type' => 'queue', 'queue_name' => '', 'timeout' => 60],
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
                    ['type' => 'queue', 'queue_name' => '', 'timeout' => 120],
                    ['type' => 'voicemail', 'mailbox' => '1000', 'vm_type' => 'u'],
                    ['type' => 'hangup'],
                ],
            ],
            [
                'name'        => 'File cascade',
                'description' => 'Distribue via une file, si pas de reponse redirige vers une seconde',
                'icon'        => 'bi-arrow-down-circle',
                'steps'       => [
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'queue', 'queue_name' => '', 'timeout' => 30],
                    ['type' => 'queue', 'queue_name' => '', 'timeout' => 60],
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
            [
                'name'        => 'Bureau Lun-Ven 9h-18h',
                'description' => 'Verifie les horaires d\'ouverture (lun-ven 09:00-18:00), accueil + file si ouvert, messagerie si ferme',
                'icon'        => 'bi-clock-history',
                'steps'       => [
                    ['type' => 'time_condition', 'time_start' => '09:00', 'time_end' => '18:00', 'days' => 'mon-fri', 'closed_sound' => 'custom/ferme', 'closed_action' => 'voicemail', 'closed_target' => '1000'],
                    ['type' => 'answer', 'wait' => 1],
                    ['type' => 'playback', 'sound' => 'custom/welcome'],
                    ['type' => 'queue', 'queue_name' => '', 'timeout' => 60],
                    ['type' => 'voicemail', 'mailbox' => '1000', 'vm_type' => 'u'],
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
