<p></p><p align="center"><img src="https://i.ibb.co/LPV7ftY/logo.png" width="240" alt="AIS Logo"></p><p></p><p></p>

AIS v6 PHP Framework
===================

AIS Framework is designed to be simple and efficient, allowing developers to quickly build and deploy applications. Its architecture promotes clean and maintainable code, making it a great choice for both beginners and experienced developers looking for a robust, scalable solution.

* * *
Installation
-------------
Clone, then use:
```
git clone https://github.com/pwwiur/ais.git
```
* * *

AIS Structure
-------------

The AIS architecture is designed with simplicity at its core. This simplicity facilitates an intuitive understanding of the system, making it easier for developers to interact with and modify the framework. By minimizing complexity, AIS ensures that developers can focus more on developing features rather than wrestling with the framework itself.
```
---- app
-------- core
-------- controller
-------- model
-------- view
---- public
-------- js
-------- css
-------- img
```
* * *

Routing using Controllers
-------------------------

In the AIS Framework, routes are defined using a straightforward folder and file structure in controller folder. Each folder represents a potential route segment, and each file within those folders can be accessed as an endpoint. For example:
```
---- app
-------- controller
------------ home.php                   (Accessible via /)
------------ user
---------------- home.php               (Accessible via /user/)
---------------- transactions.php       (Accessible via /user/transactions)
---------------- edit-profile.php       (Accessible via /user/edit-profile)
```
This structure allows for easy mapping of URLs to their corresponding controllers and views, simplifying the routing process.

### Middleware, Preprocess and Postprocess

There is a middleware, preprocess and postprocess artitecture in AIS to prevent code duplication and make the code more maintainable.

Take a look at this example:
```
---- app
-------- controller
------------ home.php
------------ middleware.php
------------ user
---------------- home.php
---------------- preprocess.php
---------------- postprocess.php
```
When client comes to `/user/`, the following steps will be taken:

1.  Middleware `controller/middleware.php` will be executed.
2.  Preprocess `controller/user/preprocess.php` will be executed.
3.  Controller `controller/user/home.php` will be executed.
4.  Postprocess `controller/user/postprocess.php` will be executed.

* * *

Views
-----

You can load a view using the `view(view_path, data, options)` function.
```php
<?php
    // controller/user/home.php
    $user = database::select('users', '*', ['id' => $_SESSION['user_id']]);
    view('user/home', ['user' => $user], ['title' => 'Dashboard']);
?>
```
If there is `layout.php`, It will be included in the view automatically.
```
---- app
-------- view
------------ layout.php
------------ home.php
------------ user
---------------- home.php
---------------- transactions.php
---------------- edit-profile.php
```
You will use user data in `/user/home` view.
```php
<?php
    // view/user/home.php
    echo "Welcome, " . $user['name'] . "!";
?>
```
* * *

APIs
----

You can easily create APIs using AIS toolkit.
```php
<?php
    // controller/api/user/get-users.php
    $users = database::select('users', '*', ['LIMIT' => 1]);
    response($users);
?>
```
The output will be in JSON format.
```json
{
    "meta": {
        "status": "SUCCESS"
    },
    "data": [
        {
            "id": 1,
            "name": "John Doe"
        }
    ]
}
```
* * *

CLIs
----

You can easily create CLI commands using AIS toolkit.
```php
<?php
    // controller/_cli/jobs/deactivate-users.php
    $inactive_users = database::select('users', '*', ['active' => 1]);
    ...
    cout("Deactivated users: " . count($inactive_users));
?>
```
You can run it using this command:

```php index.php -r jobs/deactivate-users```

* * *

Models
------

In AIS, it is preferred not to use models for simpilicity. However, In controllers, you can load models using the `model(model_path)` function when needed.

In AIS, models are simple and easy to use. They are located in the `app/model` folder and are used to interact with the database.
```php
<?php
    // controller/user/home.php
    model('user');
    // or 
    require MODEL . 'user.php';

    $user = getUser($_SESSION['user_id']);
    view('user/home', ['user' => $user], ['title' => 'Dashboard']);
?>
```
### Medoo as database wrapper

AIS uses a customized version of [Medoo](https://medoo.in/doc) as the database wrapper. It is easy to use and easy to understand.
```php
<?php
    // model/user.php
    function getUser($user_id) {
        return database::select('users', '*', ['id' => $user_id]);
    }
    function activateUser($user_id) {
        return database::update('users', ['active' => 1], ['id' => $user_id]);
    }
...
?>
```
* * *

AIS Rules
---------

**JUST DONT MAKE IT COMPLEX!**

**AIS Rule 1:** For simplicity, Not to use classes for controllers and models to ensure easy implementations.

**AIS Rule 2:** For simplicity, Every file is only one request, ensuring that files remain small and manageable.

**AIS Rule 3:** For simplicity, No need to specify HTTP method in requests and routings, file names are enough!

**AIS Rule 4:** For simplicity, Not using models are recommended.

* * *

ToolKit
-------

Here is a full use for the methods in of AIS Framework: [Toggle AIS Toolkit](javascript:void(0);) function toggleVisibility(id) { var element = document.getElementById(id); if (element.style.display === 'none') { element.style.display = 'block'; } else { element.style.display = 'none'; } }

### http_check($options)

Forces a page to be accessed via HTTP request method and content type. You should call this function at the beginning of your controller. If the conditions are not met, the script will terminate with HTTP error.

**Parameters:**

*   `$options` (array): Options for method and content type.

**Example Usage:**
```php
<?php
    // controller/user/home.php
    http_check(['method' => 'POST', 'content_type' => 'application/json']);

    $data = json_decode(file_get_contents('php://input'), true);
?>
```

### model($model)

Loads a model file or a directory contaning model files.

**Parameters:**

*   `$model` (string): The model path in `app/model` folder.

**Example Usage:**
```php
<?php
    // controller/user/home.php
    model('user');
?>
```

### view($view, $data = [], $options = [])

Loads a view file with data and options.

**Parameters:**

*   `$view` (string): The view path in `app/view` folder.
*   `$data` (array): Data is an associative array to be extracted into the view as variables.
*   `$options` (array): Options for loading layout like `['title' => 'Home Page', 'description' => 'Home Page Description', 'load_layout' => true]`. these options are accessible using `$_VIEW` variable.

**Example Usage:**
```php
<?php
    // controller/user/home.php
    $user = database::select('users', '*', ['id' => $_SESSION['user_id']]);
    view('user/home', ['user' => $user], ['title' => 'Dashboard', 'load_layout' => true]);
?>
```

### render($view, $data = [], $options = [])

Renders a view and returns the output buffer content. instead of using `view()` to echo output to client, you can use `render()` to get the output buffer content.

**Parameters:**

*   `$view` (string): The view path in `app/view` folder.
*   `$data` (array): Data is an associative array to be extracted into the view as variables.
*   `$options` (array): Options for loading layout like `['title' => 'Home Page', 'description' => 'Home Page Description', 'load_layout' => true]`. these options are accessible using `$_VIEW` variable.

**Example Usage:**
```php
<?php
    // controller/user/home.php
    $user = database::select('users', '*', ['id' => $_SESSION['user_id']]);
    $view = render('user/home', ['user' => $user], ['title' => 'Dashboard', 'load_layout' => true]);
    echo $view;
?>
```
  

### lib($lib)

Loads a library file or a library directory with `init.php` file.

**Parameters:**

*   `$lib` (string): The library path in `app/lib` folder.

**Example Usage:**
```php
<?php
    // controller/user/home.php
    lib('upload');
    $uploader = new upload();
?>
```

### redirect($url)

Redirects to a specified URL and terminates the script.

**Parameters:**

*   `$url` (string): The URL to redirect to.

**Example Usage:**
```php
redirect('https://example.com');
```
### cout($data, $delimiter = 'n')

Outputs data with an optional delimiter to the console. This name is given from c++ language.

**Parameters:**

*   `$data` (mixed): The data to output.
*   `$delimiter` (string): The delimiter to append.

**Example Usage:**
```php
cout('Hello World', "n");
```

### response($data, $meta = [])

Sends a response with data and optional metadata to user in APIs then terminates the script.

**Parameters:**

*   `$data` (mixed): The response data.
*   `$meta` (array): Optional metadata.

**Example Usage:**
```php
response(['sum' => 100], ['status' => 'SUCCESS']); 
// Output to endpoint: {"data":{"sum":100},"meta":{"status":"SUCCESS"}}
```

### status($code, $data = [])

Sends a response with a status and data to user in APIs then terminates the script.

**Parameters:**

*   `$code` (string): The status to send to user.
*   `$data` (array): The response data.

**Example Usage:**
```php
status('PROCESS_ERROR', ['error' => 'Something went wrong']);
// Output to endpoint: {"data":{"error":"Something went wrong"},"meta":{"status":"PROCESS_ERROR"}}
```

### success($data = [])

Sends a success response with data.

**Parameters:**

*   `$data` (array): The response data.

**Example Usage:**
```php
success();
// Output to endpoint: {"data":[],"meta":{"status":"SUCCESS"}}
```

### fail($data = [], $status = "FAILED")

Sends a failure response with data and status.

**Parameters:**

*   `$data` (array): The response data.
*   `$status` (string): The status code.

**Example Usage:**
```php
fail(['error' => 'Invalid request'], 'ERROR');
```

### http_status($code, $data = [], $meta = [])

Sends an HTTP status code with optional data and metadata.

**Parameters:**

*   `$code` (int): The HTTP status code.
*   `$data` (array): The response data.
*   `$meta` (array): Optional metadata.

**Example Usage:**
```php
http_status(404); // Shows browser 404 error.
```
**Example Usage:**
```php
http_status(404, ['error' => 'Not found'], ['status' => 'ERROR']); 
// HTTP error 404 + Output to endpoint: {"data":{"error":"Not found"},"meta":{"status":"ERROR"}}
```
**Example Usage:**
```php
http_status(404, "Page not found!"); 
// HTTP error 404 + Output text to endpoint: Page not found!
```
### do_nothing()

Does nothing :). It is used for code beauty or just a placeholder for future development.

**Example Usage:**
```php
    do_nothing();
```
### dump($var, $die = false)

Dumps (`var_dump`) a variable and optionally terminates the script.

**Parameters:**

*   `$var` (mixed): The variable to dump.
*   `$die` (bool): Whether to terminate the script.

**Example Usage:**
```php
    dump($var, true);
```
### d($var, $die = false)

Alias for `dump` method.

**Parameters:**

*   `$var` (mixed): The variable to dump.
*   `$die` (bool): Whether to terminate the script.

**Example Usage:**
```php
    d($var, true);
```
### dd($var)

Alias For "Dump and Die". Dumps a variable and terminates the script.

**Parameters:**

*   `$var` (mixed): The variable to dump.

**Example Usage:**
```php
    dd($var);
```
### strand($length = 10)

Generates a random alphanumeric string of a specified length.

**Parameters:**

*   `$length` (int): The length of the random string.

**Example Usage:**
```php
    strand(8); // Output: "9S34zD7o"
```

### post($url, $data)

Sends a POST `application/x-www-form-urlencoded` request with form data.

**Parameters:**

*   `$url` (string): The URL to send the request to.
*   `$data` (array): The form data to send.

**Example Usage:**
```php
    post('https://example.com/api/data', ['key' => 'value']);
```

### post_json($url, $data)

Sends a POST `application/json` request with JSON data.

**Parameters:**

*   `$url` (string): The URL to send the request to.
*   `$data` (array): The JSON data to send.

**Example Usage:**
```php
    post_json('https://example.com/api/data', ['key' => 'value']);
```

### backtrace()

Outputs a backtrace of the current call stack.

**Example Usage:**
```php
    backtrace(); // Outputs a backtrace of the current call stack.
```

### close_everything()

Flushes the output buffer and closes the connections.

**Example Usage:**
```php
    close_everything();
```

### die_gracefully()

Closes everything and terminates the script.

**Example Usage:**
```php
    die_gracefully();
```
