<?php

namespace Database\Seeders;

use App\Models\CallContext;
use Illuminate\Database\Seeder;

class CallContextSeeder extends Seeder
{
    public function run(): void
    {
        $contexts = [
            [
                'name'             => 'from-internal',
                'direction'        => 'internal',
                'description'      => 'Appels entre extensions internes',
                'dial_pattern'     => '_1XXX',
                'destination_type' => 'extensions',
                'timeout'          => 30,
                'priority'         => 1,
                'enabled'          => true,
            ],
            [
                'name'             => 'from-trunk',
                'direction'        => 'inbound',
                'description'      => 'Appels entrants depuis les trunks SIP',
                'dial_pattern'     => '_X.',
                'destination_type' => 'extensions',
                'timeout'          => 30,
                'priority'         => 2,
                'enabled'          => true,
            ],
            [
                'name'             => 'outbound-national',
                'direction'        => 'outbound',
                'description'      => 'Appels sortants nationaux (0X...)',
                'dial_pattern'     => '_0XXXXXXXXX',
                'destination_type' => 'trunk',
                'prefix_strip'     => '0',
                'prefix_add'       => '+33',
                'timeout'          => 45,
                'priority'         => 5,
                'enabled'          => true,
            ],
            [
                'name'             => 'outbound-international',
                'direction'        => 'outbound',
                'description'      => 'Appels sortants internationaux (+...)',
                'dial_pattern'     => '_+X.',
                'destination_type' => 'trunk',
                'timeout'          => 45,
                'priority'         => 6,
                'enabled'          => true,
            ],
            [
                'name'             => 'outbound-urgences',
                'direction'        => 'outbound',
                'description'      => 'Numeros d\'urgence (15, 17, 18, 112, 114, 115, 119, 191, 196)',
                'dial_pattern'     => '_1X',
                'destination_type' => 'trunk',
                'timeout'          => 60,
                'priority'         => 1,
                'enabled'          => true,
            ],
        ];

        foreach ($contexts as $ctx) {
            CallContext::firstOrCreate(['name' => $ctx['name']], $ctx);
        }
    }
}
