<?php

return [

    'ami' => [
        'host'    => env('ASTERISK_AMI_HOST', '127.0.0.1'),
        'port'    => env('ASTERISK_AMI_PORT', 5038),
        'user'    => env('ASTERISK_AMI_USER', 'admin'),
        'secret'  => env('ASTERISK_AMI_SECRET', ''),
        'timeout' => env('ASTERISK_AMI_TIMEOUT', 5),
    ],

    'defaults' => [
        'context'   => env('SIP_DEFAULT_CONTEXT', 'from-internal'),
        'transport' => env('SIP_DEFAULT_TRANSPORT', 'transport-udp'),
        'codecs'    => explode(',', env('SIP_DEFAULT_CODECS', 'alaw,ulaw,g722')),
    ],

    'transports' => [
        'transport-udp' => 'SIP/UDP',
        'transport-tcp' => 'SIP/TCP',
        'transport-tls' => 'SIP/TLS',
        'transport-wss' => 'WebRTC',
    ],

    'codecs' => [
        'ulaw'  => ['name' => 'G.711 µ-law', 'bitrate' => '64 kbps', 'quality' => 'Excellent'],
        'alaw'  => ['name' => 'G.711 A-law', 'bitrate' => '64 kbps', 'quality' => 'Excellent'],
        'g722'  => ['name' => 'G.722',       'bitrate' => '64 kbps', 'quality' => 'HD Voice'],
        'g729'  => ['name' => 'G.729',       'bitrate' => '8 kbps',  'quality' => 'Bonne'],
        'opus'  => ['name' => 'Opus',        'bitrate' => '6-510 kbps', 'quality' => 'Supérieure'],
        'gsm'   => ['name' => 'GSM',         'bitrate' => '13 kbps', 'quality' => 'Moyenne'],
        'ilbc'  => ['name' => 'iLBC',        'bitrate' => '13.3 kbps', 'quality' => 'Bonne'],
        'speex' => ['name' => 'Speex',       'bitrate' => '2-44 kbps', 'quality' => 'Variable'],
    ],

];
