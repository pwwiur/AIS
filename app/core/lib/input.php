<?php
class input {
    private static $json_cached = false;
    private static $json_cache = null;
    private static $json_valid = false;
    private static $json_error = "";

    private static $str_cached = false;
    private static $str_cache = null;
    private static $str_valid = false;
    private static $str_error = "";

    public static function data($key, $default = null) {
        return json($key) ?? post($key) ?? get($key) ?? str($key) ?? null;
    }
    public static function get($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    public static function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    public static function put($key, $default = null) {
        $putData = self::str($key);
        return $putData[$key] ?? $default;
    }

    public static function delete($key, $default = null) {
        $deleteData = self::str($key);
        return $deleteData[$key] ?? $default;
    }

    public static function request($key, $default = null) {
        return $_REQUEST[$key] ?? $default; 
    }

    public static function file($key) {
        return $_FILES[$key] ?? null;
    }

    public static function cookie($key, $default = null) {
        return $_COOKIE[$key] ?? $default;
    }

    public static function server($key, $default = null) {
        return $_SERVER[$key] ?? $default;
    }

    public static function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }

    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function isPost() {
        return self::method() === 'POST';
    }

    public static function isGet() {
        return self::method() === 'GET';
    }

    public static function isPut() {
        return self::method() === 'PUT';
    }

    public static function isDelete() {
        return self::method() === 'DELETE';
    }

    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function raw() {
        return file_get_contents('php://input');
    }

    public static function json($key = null, $assoc = true) {
        $json = null;
        if (self::$json_cached) {
            if (!self::$json_valid) {
                return null;
            }
            $json = self::$json_cache;
        }
        else {
            $json = json_decode(self::raw(), $assoc);
            self::$json_cache = $json;
            self::$json_cached = true;
            if ($json) {
                self::$json_valid = true;
            }
            else {
                self::$json_error = json_last_error();
                self::$json_valid = false;
                if (DEBUG) {
                    throw new \Exception(json_last_error_msg());
                }
                return null;
            }
        }
        
        if (isset($key)) {
            return $json[$key];
        }
        return $json;
    }
    public static function json_error() {
        self::json();
        return self::$json_error;
    }

    public static function str($key = null, $default = null) {
        $strData = null;
        if (self::$str_cached) {
            if (!self::$str_valid) {
                return null;
            }
            $strData = self::$str_cache;
        }
        else {
            self::$str_cached = true;
            try {
                parse_str(self::raw(), $strData);
                self::$str_cache = $strData;
                self::$str_valid = true;
            } catch (Exception $e) {
                self::$str_error = $e->getMessage();
                self::$str_valid = false;
                self::$str_cache = null;
                if (DEBUG) {
                    throw $e;
                }
                return null;
            }
        }
        
        if (isset($key)) {
            return $strData[$key];
        }
        return $strData;
    }

    public static function str_error() {
        return self::$str_error;
    }
}
