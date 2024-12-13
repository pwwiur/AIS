<?php
$active_libraries = [
    'access',
    'tools',
    'database',
    'time',
    'session',
    'input'
];

if (file_exists(COMPOSER . 'autoload.php')) {
    require_once COMPOSER . 'autoload.php';
}

foreach ($active_libraries as $library) {
    require_once __DIR__ . '/' . $library . '.php';
}

