<?php
    class time {
        private static $initialized = false;
        private static $time = 0;
        function __construct() {
            self::init($agent);
        }
        public static function init() {
            if (!self::$initialized){
                self::refresh();
            }
        }
        public static function refresh() {
            self::$time = time();
        }
        public static function now($refresh = false){
            if($refresh){
                self::refresh();
            }
            return self::$time;
        }
        public static function date($type = "n", $time = NULL){
            switch ($type) {
                case 'g':
                    return date("d.m.Y", $time ?: time::now());

                default:
                    return date("Y-m-d H:i:s", $time ?: time::now());
                    break;
            }
        }
        public static function delay($seconds){
            sleep($seconds);
            return self::now(true);
        }
        public static function future($length){
            return self::$time + self::calc($length);
        }
        public static function past($length){
            return self::$time - self::calc($length);
        }
        public static function calc($length){
            $times = explode(" ", $length);
            $total = 0;
            foreach($times as $time){
                $unit = substr($time, -1);
                if(is_numeric($unit)){
                    $unit = "s";
                }
                else{
                    $time = is_numeric($time[0]) ? substr($time, 0, -1) : 1;
                }
                $total += $time * self::chartosec($unit);
            }
            return $total;
        }
        public static function chartosec($char){
            switch ($char) {
                case 'i':
                    $sec = 60;
                    break;
                case 'h':
                    $sec = 3600;
                    break;
                case 'd':
                    $sec = 86400;
                    break;
                case 'w':
                    $sec = 604800;
                    break;
                case 'm':
                    $sec = 2592000;
                    break;
                case 'y':
                    $sec = 31536000;
                    break;
                default:
                    $sec = 1;
                    break;
            }
            return $sec;
        }
    }
    time::init();
