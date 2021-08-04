<?php

return [

    /*
     * The disk on which to store added files and derived images by default. Choose
     * one or more of the disks you've configured in config/filesystems.php.
     */
    'disk_name' => env('MEDIA_DISK', 'public'),

    'disk_size' => 1024,

    /*
     * The maximum file size of an item in bytes.
     * Adding a larger file will result in an exception.
     */
    'max_file_size' => 1024 * 1024 * 10,

    'conversion' => [
        'default' => [
            'path' => 'default'
        ],
        'thumbnail' => [
            'path' => 'thumbnails',
            'width' => 500
        ],
        'small_thumbnail' => [
            'path' => 'small_thumbs',
            'width' => 200
        ]
    ]
];
