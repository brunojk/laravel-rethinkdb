<?php

return [
    'connections' => [
        'rethinkdb' => [
            'name'      => 'rethinkdb',
            'driver'    => 'rethinkdb',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', 28015),
            'database'  => env('DB_DATABASE', 'unittest'),
            'user'      => env('DB_USERNAME', 'admin'),
            'password'  => env('DB_PASSWORD', 'dev6969'),
        ],
    ],
];
