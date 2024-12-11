<?php
    require __DIR__ . '/config/loader.php';
    require __DIR__ . '/http/loader.php';
    require __DIR__ . '/lib/loader.php';

    if (POWER == 0) {
        http_status(503);
    }
    if (CLI) {
        $shortopts = "hvr:";  // Short options: h (help), v (version), r (request)
        $longopts = array(
          "help",
          "version",
          "request:",
        );
        
        $options = getopt($shortopts, $longopts);
        
        if (isset($options['v']) || isset($options['version'])) {
            $target = "/_cli/v";
        } 
        else if(isset($options['r']) || isset($options['request'])){
          $request = isset($options['r']) ? $options['r'] : $options['request'];
          $target = "/_cli/" . $request;
        }
        else {
            $target = "/_cli/h";
        }
    }
    else {
        $target = URN;
    }

    if (strpos($target, '..') !== false || strpos($target, '.php') !== false) {
        http_status(404);
    }

    if (empty($target) || $target == '/') {
        $target = '/home';
    }

    $levels = explode('/', trim($target, "/"));
    $link_vars = [];
    $middlewares = [];
    $level_path = CONTROLLER;
    foreach ($levels as $level) {
        if (in_array($level, ["_cli", "middleware", "preprocess", "postprocess", "dynamic"])) {
            http_status(404);
        }

        $candid_level_path = $level_path . "/" . $level;
        $dynamic_folder = $level_path . '/dynamic';
        $dynamic_file = $level_path . '/dynamic.php';

        if ($level === end($levels)) {
            if (file_exists($candid_level_path . HOME)) {
                $level_path .= "/" . $level . HOME;
            }
            else if (file_exists($candid_level_path . PHP)) {
                $level_path .= "/" . $level . PHP;
            }
            else if (file_exists($dynamic_folder . HOME)) {
                $link_vars[] = $level;
                $level_path = $dynamic_folder . HOME;
            }
            else if (file_exists($dynamic_file)) {
                $link_vars[] = $level;
                $level_path = $dynamic_file;
            }
            else {
                http_status(404);
            }
        }
        else {
            if (is_dir($candid_level_path)) {
                $level_path .= "/" . $level;
            }
            else if (is_dir($dynamic_folder)) {
                $link_vars[] = $level;
                $level_path = $dynamic_folder;
            }
            else {
                http_status(404);
            }

            $middleware_file = $level_path . '/middleware.php';

            if (file_exists($middleware_file)) {
                $middlewares[] = $middleware_file;
            }
        }

        
    }

    $controller = $level_path;

    foreach ($middlewares as $middleware) {
        require $middleware;
    }

    $preprocess_file = dirname($controller) . '/preprocess.php';
    if (file_exists($preprocess_file)) {
        require $preprocess_file;
    }

    require $controller;

    $postprocess_file = dirname($controller) . '/postprocess.php';
    if (file_exists($postprocess_file)) {
        require $postprocess_file;
    }

    die_gracefully();