<?php

declare(strict_types=1);

return [


    'disk' => 'local', // backup - need define in disks in config/filesystems.php
    'directory' => 'mysql-backup',

    /* placeholder - do not use here
    |--------------------------------------------------------------------------
    | Filesystem disks
    |--------------------------------------------------------------------------
    |
    | Each listed disk is registered unless the host application already has
    | a disk with the same name in config/filesystems.php.
    |
    */
    // 'disks' => [
    //     'backup' => [
    //         'driver' => 'local',
    //         'root' => storage_path(),
    //         'throw' => false,
    //     ],
    // ],
];
