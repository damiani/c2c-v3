<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Storage
    |--------------------------------------------------------------------------
    |
    | Documents, signed packages, previews, and generated artifacts must be
    | stored outside the application database. Local development uses the
    | private "documents" disk; deployed environments should point this value
    | at an S3-compatible disk such as "s3".
    |
    */

    'storage' => [
        'disk' => env('DOCUMENT_STORAGE_DISK', 'documents'),
        'path_prefix' => trim((string) env('DOCUMENT_STORAGE_PATH_PREFIX', 'documents'), '/'),
        'visibility' => env('DOCUMENT_STORAGE_VISIBILITY', 'private'),
    ],

];
