<?php

return [
    'isBanned' => [
        'table'      => 'User',
        'conditions' => [
            'blocked' => 1,
            'user_id' => ':chatId'
        ]
    ],
    'isAdmin' => [
        'table'      => 'User',
        'conditions' => [
            'role'    => 'ADMIN',
            'user_id' => ':chatId'
        ]
    ],
    'isBannedBot' => [
        'table'       => 'Bot',
        'conditions'  => [
            'blocked' => 1,
            'chatId'  => ':chatId'
        ]
    ]
];