<?php
// Example only. The installer (install/index.php) generates config/config.php automatically.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'school_erp',
        'user' => 'root',
        'pass' => '',
    ],
    'timezone' => 'Asia/Karachi',
    'debug' => false,            // never true on a live server
    'app_key' => 'generated-by-installer',
];
