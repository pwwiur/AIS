<?php
    ob_start();

    if (DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', true);
        ini_set('display_startup_errors', true);
    }

    define('CLI', PHP_SAPI === 'cli');

    if(CLI) {
        ignore_user_abort(true);
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        ini_set("memory_limit", '512M');
    }
    else{
        session_start();
        header('CMS-NAME: ' . CMS_NAME . ' v' . CMS_VERSION);
    }
    date_default_timezone_set('UTC');