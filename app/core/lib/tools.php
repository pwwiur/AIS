<?php
    function http_check($options){
        if(CLI or !isset($options) or empty($options)) return;
        if(isset($options["method"]) && $_SERVER["REQUEST_METHOD"] !== $options["method"]) {
            http_status(405);
        }
        if(isset($options["content_type"]) && strpos($_SERVER["CONTENT_TYPE"], $options["content_type"]) === false) {
            http_status(415);
        }
    }
    function lib($lib) {
        $lib = trim(str_replace(D, S, $lib), S);
        is_dir(LIB . $lib) ? require_once LIB . $lib . S . "init.php" : require_once LIB . $lib . PHP;
    }
    function model($model) {
        $model = trim(str_replace(D, S, $model), S);
        if(is_dir(MODELS . $model)) {
            foreach(glob(MODELS . $model . S . "*" . PHP) as $file) {
                require $file;
            }
        }
        else {
            require MODELS . $model . S . PHP;
        }
    }
    function view($view, $data = [], $options = []){
        $view = trim(str_replace(D, S, $view), S);
        $pathinfo = pathinfo($view);
        $pathinfo["dirname"] = $pathinfo["dirname"] === "." ? "" : $pathinfo["dirname"];
        extract($data);
        $_VIEW = $options;
        $loadLayout = isset($_VIEW["load_layout"]) ? $_VIEW["load_layout"] : true;
        $loadRootLayout = isset($_VIEW["load_root_layout"]) ? $_VIEW["load_root_layout"] : true;
        if($loadLayout && file_exists(VIEW . $pathinfo["dirname"] . "/layout.php")){
            $_VIEW['file'] = $pathinfo["basename"] . PHP;
            require VIEW . $pathinfo["dirname"] . "layout.php";
        }
        elseif($loadLayout && $loadRootLayout && file_exists(VIEW . "layout.php")){
            $_VIEW['file'] = $pathinfo["dirname"] . S . $pathinfo["basename"] . PHP;
            require VIEW . "layout.php";
        }
        else{
            require VIEW . $view . PHP;
        }
    }
    function render($view, $data = [], $options = []){
        ob_start();
        view($view, $data, $options);
        return ob_get_clean();
    }
    function close_everything() {
        session_write_close();
    }
    function die_gracefully() {
        close_everything();
        die();
    }
    function redirect($url){
        header("location:" . $url);
	    die_gracefully();
    }
    function cout($data, $delimiter = N) {
		if(is_callable($data)) {
			echo $data();
		}
		else if (is_array($data)) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        else {
            echo $data;
        }
        echo $delimiter;
    }
    function response($data, $meta = []) {
		if(is_callable($data)) {
			echo $data();
		}
		else if (is_array($data)) {
            header('Content-Type: application/json; charset=utf-8');
            if(empty($meta)) {
                $meta = ["status" => "SUCCESS"];
            }
            echo json_encode([
                "meta" => $meta,
                "data" => $data
            ], JSON_UNESCAPED_UNICODE);
        }
        else {
            echo $data;
        }
		die_gracefully();
	}
	function status($code, $data = []){
	    response($data, ["status" => $code]);
	}
    function success($data = []) {
        status("SUCCESS", $data);
    }
    function fail($data = [], $status = "FAILED") {
        status($status, $data);
    }
    function http_status($code, $data = [], $meta = []) {
        if (CLI) {
            cout("Error: " . $code, N);
        }
        else {
            http_response_code($code);
        }
        if (!empty($data) || !empty($meta)) {
            response($data, $meta);
        }
        else {
            die_gracefully();
        }
    }
	function do_nothing () {
	    /* OK, nothing :D */
	}
	function dump($var, $die = false){
	    var_dump($var);
        if($die) die_gracefully();
    }
	function d($var, $die = false) {
        dump($var, $die);
    }
    function dd(...$vars){
        foreach ($vars as $v) {
            dump($v, false);
        }
        die_gracefully();
    }
	function strand($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strand = '';
        for ($i = 0; $i < $length; $i++)
            $strand .= $characters[rand(0, strlen($characters)- 1)];

        return $strand;
    }
    function post($url, $data){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    function post_json($url, $data){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    function backtrace(){
        foreach(debug_backtrace(false) as $trace)
            echo "{$trace['file']}::{$trace['line']}" . N;
    }