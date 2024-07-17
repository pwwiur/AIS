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
            $target = "/cli/v";
        } 
        else if(isset($options['r']) || isset($options['request'])){
          $request = isset($options['r']) ? $options['r'] : $options['request'];
          $target = "/cli/" . $request;
        }
        else {
            $target = "/cli/h";
        }
    }
    else {
        $target = URN;
    }

    if (strpos($target, '..') !== false) {
        http_status(404);
    }

    if (empty($target) || $target == '/') {
        $target = '/home';
    }

    if (is_dir(CONTROLLER . $target) and file_exists(CONTROLLER . $target . '/home.php')) {
        $target .= '/home';
    }


    $levels = explode('/', $target);
    $controller = CONTROLLER . $target . '.php';

    if ($levels[0] == 'cli' and !CLI) {
        http_status(404);
    }

    if (file_exists($controller)) {
        $level_path = "";
        foreach ($levels as $level) {
            $level_path .= "/" . $level;
            $middleware_file = CONTROLLER . $level_path . '/middleware.php';
            if (file_exists($middleware_file)) {
                require $middleware_file;
            }
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
    } 
    else {
        http_status(404);
    }

    die_gracefully();