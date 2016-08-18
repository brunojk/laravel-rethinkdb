<?php

return [

    'clustering' => 'default'

//        configs per table - based on https://www.rethinkdb.com/api/javascript/reconfigure/

/*
    'clustering' => [

//        configs default for all tables
        'all_tables' => [
            'shards' => 1,
            'replicas' => [
                'server_tag' => 1
            ],
            'primaryReplicaTag' => 'server_tag'
        ],

//        configs of this table will override the all_tables
        'users' => 'default'
    ]
*/

];
