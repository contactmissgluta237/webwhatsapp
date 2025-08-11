<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Livewire Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire will utilize a temporary directory for file uploads. This is
    | the disk that the files will be stored on and the prefix that will
    | be added to the file names.
    |
    */

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TMP_DISK', 'livewire_tmp'),
        'path' => null,
        'middleware' => [
            'throttle:60,1',
        ],
        'preserve_file_names' => false,
    ],
];
