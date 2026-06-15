<?php

return [
    'default_disk' => env('FILES_DEFAULT_DISK', 'local'),

    'max_file_size' => env('FILES_MAX_FILE_SIZE', 20480),

    'chunk_size' => env('FILES_CHUNK_SIZE', 5242880),

    'download_link_ttl_hours' => env('FILES_DOWNLOAD_LINK_TTL_HOURS', 24),

    'allowed_mime_types' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
];
