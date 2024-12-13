<?php
class session {
    private static $middleware = [
        'set' => [],
        'get' => [],
        'remove' => [],
        'destroy' => [],
        'generate' => [],
    ];

    public static function set($key, $value) {        
        $_SESSION[$key] = $value;
        foreach (self::$middleware['set'] as $middleware) {
            $middleware($key, $value);
        }
    }

    public static function get($key, $default = null) {
        $value = $_SESSION[$key] ?? $default;
        foreach (self::$middleware['get'] as $middleware) {
            $value = $middleware($key, $value);
        }
        return $value;
    }

    public static function has($key) {
        $exists = isset($_SESSION[$key]);
        return $exists;
    }

    public static function remove($key) {
        unset($_SESSION[$key]);
        foreach (self::$middleware['remove'] as $middleware) {
            $middleware($key);
        }
    }

    public static function destroy() {
        foreach ($_SESSION as $key => $_) {
            foreach (self::$middleware['remove'] as $middleware) {
                $middleware($key);
            }
        }
        session_destroy();
        $_SESSION = array();
        foreach (self::$middleware['destroy'] as $middleware) {
            $middleware();
        }
    }

    public static function regenerate() {
        session_regenerate_id(true);
        foreach (self::$middleware['generate'] as $middleware) {
            $middleware();
        }
    }

    public static function on($event, $middleware) {
        self::$middleware[$event][] = $middleware;
    }
}
