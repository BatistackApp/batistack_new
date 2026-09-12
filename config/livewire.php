<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire temporarily stores uploaded files before persisting them to
    | their final destination. By default, these temporary uploads are stored
    | on the "local" disk to avoid CORS issues with S3 and other cloud disks.
    |
    */

    'temporary_file_upload' => [
        'disk' => 'local',
        'directory' => 'livewire-tmp',
    ],

];
