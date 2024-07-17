<?php
    if(!CLI){
        define('IP', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR']);
        define('AGENT', $_SERVER['HTTP_USER_AGENT']);
        define('REQUEST', $_SERVER['HTTP_HOST']);
        define('PROTOCOL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://');
        define('REQURI', $_SERVER['REQUEST_URI']);
        define('REQER', REQURI);
        define('URN', explode("?", REQURI)[0]);
        define('URL', PROTOCOL . REQUEST . REQURI);
    }