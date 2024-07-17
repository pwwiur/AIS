<?php
    // access is used to access global variables and store process logs
    class access {
        private static $initialized = false;
        public static $language_name;
        public static $language;
        public static $infos;
        public static $errors;
        public static $warnings;
        public static $logs;
        public static $global;
        function __construct(){
            self::init();

        }
        public static function init(){
            if (!self::$initialized){
                self::$language_name = "default";
                self::$language = [];
                self::$infos = [];
                self::$errors = [];
                self::$warnings = [];
                self::$logs = [];
                self::$global = [];
            }
        }
        public static function load_language($lang){
            self::$language_name = $lang;
            if(file_exists(LANGUAGE . self::$language_name . ".json")){
                self::$language = json_decode(file_get_contents(LANGUAGE . self::$language_name . ".json"), true);
            }
        }
    }
    access::init();
