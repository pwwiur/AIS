<?php
    class cookie {
        private static $initialized = false;
        private static $id;
        private static $items;
        function __construct(){
            self::init();
        }
        public static function init(){
            if (!self::$initialized){
                self::$id = database::select("cookie", "id", [
                        "AND" => [
                            "ip" => agent::$ip,
                            "platform" => agent::$platform,
                            "app" => agent::$app,
                            "token" => $_COOKIE["ais"],
                            "expire[>]" => time::now()
                        ]
                    ]
                )[0];
                $token = strand(16);
                if(has(self::$id)){
                    database::update("cookie", ["token" => $token, "expire" => time::future("w")], ["id" => self::$id]);
                    setcookie("ais", $token, time::future("w"), "/");
                    foreach(database::select("cookie_item", "*", ["cookie" => self::$id]) as $item){
                        self::$items[$item["key"]] = $item["value"];
                    }
                }
                else{
                    database::delete("cookie", ["token" => $_COOKIE["ais"]]);
                    database::delete("cookie", [
                        "AND" => [
                            "ip" => agent::$ip,
                            "platform" => agent::$platform,
                            "app" => agent::$app,
                        ]
                    ]);
                    self::$id = database::insert("cookie", [
                        "ip" => agent::$ip,
                        "platform" => agent::$platform,
                        "app" => agent::$app,
                        "token" => $token,
                        "period" => time::calc("w"),
                        "expire" => time::future("w"),
                        "time" => time::now()
                    ]);
                    setcookie("ais", $token, time::future("w"), "/");
                }
            }
        }
        public static function get($key){
            return self::$items[$key];
        }
        public static function has($key){
            return isset(self::$items[$key]);
        }
        public static function set($key, $value = 1, $urn = "/"){
            if(database::has("cookie_item", ["AND" => ["cookie" => self::$id, "key" => $key, "urn" => $urn]])){
                database::update("cookie_item", [
                    "value" => $value,
                ], ["AND" => ["cookie" => self::$id, "key" => $key, "urn" => $urn]]);
            }
            else{
                database::insert("cookie_item", [
                    "cookie" => self::$id,
                    "key" => $key,
                    "value" => $value,
                    "urn" => $urn
                ]);
            }
            self::$items[$key] = $value;
        }
        public static function delete($key){
            database::delete("cookie_item", [
                "AND" => [
                    "cookie" => self::$id,
                    "key" => $key
                ]
            ]);
            unset(self::$items[$key]);
        }
    }
    cookie::init();