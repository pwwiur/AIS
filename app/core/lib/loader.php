<?php
$active_libraries = [
    'access',
    'tools',
    'database',
    'time',
];


foreach ($active_libraries as $library) {
    require_once __DIR__ . '/' . $library . '.php';
}

