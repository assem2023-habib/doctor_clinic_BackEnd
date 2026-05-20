<?php

return [
    'channels' => [
        'log' => [
            'enabled' => true,
        ],
        'database' => [
            'enabled' => true,
        ],
        'firebase' => [
            'enabled' => env('FCM_ENABLED', false),
            'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
        ],
        'websocket' => [
            'enabled' => env('REVERB_ENABLED', false),
            'app_id' => env('REVERB_APP_ID'),
            'app_key' => env('REVERB_APP_KEY'),
            'app_secret' => env('REVERB_APP_SECRET'),
            'host' => env('REVERB_HOST', 'localhost'),
            'port' => env('REVERB_PORT', 8080),
        ],
        'socketio' => [
            'enabled' => env('SOCKET_IO_ENABLED', false),
            'server_url' => env('SOCKET_IO_SERVER_URL', 'http://localhost:3000'),
            'secret' => env('SOCKET_IO_SECRET'),
        ],
    ],

    'events' => [
        'appointment.requested' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.time_set' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.accepted' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.rejected' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.cancelled' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.in_progress' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.completed' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'appointment.alternative_suggested' => ['log', 'database', 'firebase', 'websocket', 'socketio'],

        'login.new_device' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
        'login.suspicious_activity' => ['log', 'database', 'firebase', 'websocket', 'socketio'],
    ],
];
