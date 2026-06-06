<?php

return [
    'start_screen' => [
        'title'            => 'memories',
        'title_visible'    => true,
        'subtitle'         => 'memories',
        'subtitle_visible' => true,
    ],
    'ui' => [
        'skip_welcome' => true,
    ],
    'dev' => [
        'demo_images' => false,
    ],
    'picture' => [
        'time_to_live' => 30,
    ],
    'preview' => [
        'mode'        => 'device_cam',
        'camTakesPic' => true,
        'flip'        => 'off',
    ],
    'qr' => [
        'enabled' => false,
        'result'  => 'hidden',
        'pswp'    => 'hidden',
    ],
    'database' => [
        'enabled' => true,
        'file'    => 'db',
    ],
];
