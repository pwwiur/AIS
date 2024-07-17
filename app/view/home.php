<h1>AIS v<?=CMS_VERSION?> PHP Framework</h1>
<p>Ais is working fine now: <?=$current_date?></p>
<p>AIS Framework is designed to be simple and efficient, allowing developers to quickly build and deploy applications. Its architecture promotes clean and maintainable code, making it a great choice for both beginners and experienced developers looking for a robust, scalable solution.</p>
<hr>

<h2>AIS Structure</h2>
<p>The AIS architecture is designed with simplicity at its core. This simplicity facilitates an intuitive understanding of the system, making it easier for developers to interact with and modify the framework. By minimizing complexity, AIS ensures that developers can focus more on developing features rather than wrestling with the framework itself.</p>
<pre>
---- app
-------- core
-------- controller
-------- model
-------- view
---- public
---- composer
</pre>
<hr>

<h2>Routing using Controllers</h2>
<p>In the AIS Framework, routes are defined using a straightforward folder and file structure in controller folder. Each folder represents a potential route segment, and each file within those folders can be accessed as an endpoint. For example:</p>
<pre>
---- app
-------- controller
------------ home.php                   (Accessible via /)
------------ user
---------------- home.php               (Accessible via /user/)
---------------- transactions.php       (Accessible via /user/transactions)
---------------- edit-profile.php       (Accessible via /user/edit-profile)

</pre>
<p>This structure allows for easy mapping of URLs to their corresponding controllers and views, simplifying the routing process.</p>
<h3>Middleware, Preprocess and Postprocess</h3>
<p>There is a middleware, preprocess and postprocess artitecture in AIS to prevent code duplication and make the code more maintainable.</p>
<p>Take a look at this example:</p>
<pre>
---- app
-------- controller
------------ home.php
------------ middleware.php
------------ user
---------------- home.php
---------------- preprocess.php
---------------- postprocess.php
</pre>
<p>When client comes to <code>/user/</code>, the following steps will be taken:</p>
<ol>
<li>Middleware <code>controller/middleware.php</code> will be executed.</li>
<li>Preprocess <code>controller/user/preprocess.php</code> will be executed.</li>
<li>Controller <code>controller/user/home.php</code> will be executed.</li>
<li>Postprocess <code>controller/user/postprocess.php</code> will be executed.</li>
</ol>
<hr>


<h2>Views</h2>
<p>You can load a view using the <code>view(view_path, data, options)</code> function.</p>
<pre>
&lt;?php
    // controller/user/home.php
    $user = database::select('users', '*', ['id' => $_SESSION['user_id']]);
    view('user/home', ['user' => $user], ['title' => 'Dashboard']);
?&gt;
</pre>
If there is <code>layout.php</code>, It will be included in the view automatically.
<pre>
---- app
-------- view
------------ layout.php
------------ home.php
------------ user
---------------- home.php
---------------- transactions.php
---------------- edit-profile.php
</pre>
<p>You will use user data in <code>/user/home</code> view.</p>
<pre>
&lt;?php
    // view/user/home.php
    echo "Welcome, " . $user['name'] . "!";
?&gt;
</pre>
<hr>
<h2>APIs</h2>
<p>You can easily create APIs using AIS toolkit.</p>
<pre>
&lt;?php
    // controller/api/user/get-users.php
    $users = database::select('users', '*');
    response($users);
?&gt;
</pre>
The output will be in JSON format.
<pre>
{
    "meta": {
        "status": "SUCCESS"
    },
    "data": [
        {
            "id": 1,
            "name": "John Doe"
        },
        ...
    ]
}
</pre>
<hr>
<h2>CLIs</h2>
<p>You can easily create CLI commands using AIS toolkit.</p>
<pre>
&lt;?php
    // controller/cli/jobs/deactivate-users.php
    $inactive_users = database::select('users', '*', ['active' => 1]);
    ...
    cout("Deactivated users: " . count($inactive_users));
?&gt;
</pre>
You can run it using this command:
<pre>
php index.php -r jobs/deactivate-users
</pre>
<hr>
<h2>Models</h2>
<p>In AIS, it is preferred not to use models for simpilicity. However, In controllers, you can load models using the <code>model(model_path)</code> function when needed.</p>
<p>In AIS, models are simple and easy to use. They are located in the <code>app/model</code> folder and are used to interact with the database.</p>
<pre>
&lt;?php
    // controller/user/home.php
    model('user');
    // or 
    require MODEL . 'user.php';

    $user = getUser($_SESSION['user_id']);
    view('user/home', ['user' => $user], ['title' => 'Dashboard']);
?&gt;
</pre>
<h3>Medoo as database wrapper</h3>
<p>AIS uses a customized version of <a href="https://medoo.in/doc" target="_blank">Medoo</a> as the database wrapper. It is easy to use and easy to understand.</p>
<pre>
&lt;?php
    // model/user.php
    function getUser($user_id) {
        return database::select('users', '*', ['id' => $user_id]);
    }
    function activateUser($user_id) {
        return database::update('users', ['active' => 1], ['id' => $user_id]);
    }
...
?&gt;
</pre>
<hr>

<h2>AIS Rules</h2>
<p><b>JUST DONT MAKE IT COMPLEX!</b></p>
<p><b>AIS Rule 1:</b> For simplicity, Not to use classes for controllers and models to ensure easy implementations.</p>
<p><b>AIS Rule 2:</b> For simplicity, Every file is only one request, ensuring that files remain small and manageable.</p>
<p><b>AIS Rule 3:</b> For simplicity, No need to specify HTTP method in requests and routings, file names are enough!</p>
<p><b>AIS Rule 4:</b> For simplicity, Not using models are recommended.</p>
<hr>

<h2>ToolKit</h2>
Here is a full use for the methods in of AIS Framework:
<a href="javascript:void(0);" onclick="toggleVisibility('ais-toolkit')">Toggle AIS Toolkit</a>
<script>
function toggleVisibility(id) {
    var element = document.getElementById(id);
    if (element.style.display === 'none') {
        element.style.display = 'block';
    } else {
        element.style.display = 'none';
    }
}
</script>
<div class="ais-toolkit" id="ais-toolkit" style="display: block;">
    <div class="function-section">
        <h3>http_check($options)</h3>
        <p>Forces a page to be accessed via HTTP request method and content type. You should call this function at the beginning of your controller. If the conditions are not met, the script will terminate with HTTP error.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$options</code> (array): Options for method and content type.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>
&lt;?php
    // controller/user/home.php
    http_check(['method' => 'POST', 'content_type' => 'application/json']);

    $data = json_decode(file_get_contents('php://input'), true);
?&gt;
        </pre>
        
    </div>

    <div class="function-section">
        <h3>model($model)</h3>
        <p>Loads a model file or a directory contaning model files.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$model</code> (string): The model path in <code>app/model</code> folder.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>
&lt;?php
    // controller/user/home.php
    model('user');
?&gt;
        </pre>
    </div>

    <div class="function-section">
        <h3>view($view, $data = [], $options = [])</h3>
        <p>Loads a view file with data and options.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$view</code> (string): The view path in <code>app/view</code> folder.</li>
            <li><code>$data</code> (array): Data is an associative array to be extracted into the view as variables.</li>
            <li><code>$options</code> (array): Options for loading layout like <code>['title' => 'Home Page', 'description' => 'Home Page Description', 'load_layout' => true]</code>. these options are accessible using <code>$_VIEW</code> variable.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>
&lt;?php
    // controller/user/home.php
    $user = database::select('users', '*', ['id' => $_SESSION['user_id']]);
    view('user/home', ['user' => $user], ['title' => 'Dashboard', 'load_layout' => true]);
?&gt;
        </pre>
    </div>

    <div class="function-section">
        <h3>render($view, $data = [], $options = [])</h3>
        <p>Renders a view and returns the output buffer content. instead of using <code>view()</code> to echo output to client, you can use <code>render()</code> to get the output buffer content.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$view</code> (string): The view path in <code>app/view</code> folder.</li>
            <li><code>$data</code> (array): Data is an associative array to be extracted into the view as variables.</li>
            <li><code>$options</code> (array): Options for loading layout like <code>['title' => 'Home Page', 'description' => 'Home Page Description', 'load_layout' => true]</code>. these options are accessible using <code>$_VIEW</code> variable.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>
&lt;?php
    // controller/user/home.php
    $user = database::select('users', '*', ['id' => $_SESSION['user_id']]);
    $view = render('user/home', ['user' => $user], ['title' => 'Dashboard', 'load_layout' => true]);
    echo $view;
?&gt;
        </pre>
    </div>

    <div class="function-section">
        <h3>lib($lib)</h3>
        <p>Loads a library file or a library directory with <code>init.php</code> file.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$lib</code> (string): The library path in <code>app/lib</code> folder.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>
&lt;?php
    // controller/user/home.php
    lib('upload');
    $uploader = new upload();
?&gt;
        </pre>
    </div>

    <div class="function-section">
        <h3>redirect($url)</h3>
        <p>Redirects to a specified URL and terminates the script.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$url</code> (string): The URL to redirect to.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>redirect('https://example.com');</code></pre>
    </div>

    <div class="function-section">
        <h3>cout($data, $delimiter = '\n')</h3>
        <p>Outputs data with an optional delimiter to the console. This name is given from c++ language.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$data</code> (mixed): The data to output.</li>
            <li><code>$delimiter</code> (string): The delimiter to append.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>cout('Hello World', "\\n");</code></pre>
    </div>

    <div class="function-section">
        <h3>response($data, $meta = [])</h3>
        <p>Sends a response with data and optional metadata to user in APIs then terminates the script.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$data</code> (mixed): The response data.</li>
            <li><code>$meta</code> (array): Optional metadata.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>response(['sum' => 100], ['status' => 'SUCCESS']); 
// Output to endpoint: {"data":{"sum":100},"meta":{"status":"SUCCESS"}} </pre>
    </div>

    <div class="function-section">
        <h3>status($code, $data = [])</h3>
        <p>Sends a response with a status and data to user in APIs then terminates the script.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$code</code> (string): The status to send to user.</li>
            <li><code>$data</code> (array): The response data.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>status('PROCESS_ERROR', ['error' => 'Something went wrong']);
// Output to endpoint: {"data":{"error":"Something went wrong"},"meta":{"status":"PROCESS_ERROR"}}</code></pre>
    </div>

    <div class="function-section">
        <h3>success($data = [])</h3>
        <p>Sends a success response with data.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$data</code> (array): The response data.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre>success();
// Output to endpoint: {"data":[],"meta":{"status":"SUCCESS"}}</pre>
    </div>

    <div class="function-section">
        <h3>fail($data = [], $status = "FAILED")</h3>
        <p>Sends a failure response with data and status.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$data</code> (array): The response data.</li>
            <li><code>$status</code> (string): The status code.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>fail(['error' => 'Invalid request'], 'ERROR');</code></pre>
    </div>

    <div class="function-section">
        <h3>http_status($code, $data = [], $meta = [])</h3>
        <p>Sends an HTTP status code with optional data and metadata.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$code</code> (int): The HTTP status code.</li>
            <li><code>$data</code> (array): The response data.</li>
            <li><code>$meta</code> (array): Optional metadata.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>http_status(404); // Shows browser 404 error.</code></pre>
        <p><strong>Example Usage:</strong></p>
        <pre>http_status(404, ['error' => 'Not found'], ['status' => 'ERROR']); 
// HTTP error 404 + Output to endpoint: {"data":{"error":"Not found"},"meta":{"status":"ERROR"}}</pre>
        <p><strong>Example Usage:</strong></p>
        <pre>http_status(404, "Page not found!"); 
// HTTP error 404 + Output text to endpoint: Page not found!</pre>
    </div>

    <div class="function-section">
        <h3>do_nothing()</h3>
        <p>Does nothing :). It is used for code beauty or just a placeholder for future development.</p>
        <p><strong>Example Usage:</strong></p>
        <pre><code>do_nothing();</code></pre>
    </div>

    <div class="function-section">
        <h3>dump($var, $die = false)</h3>
        <p>Dumps (<code>var_dump</code>) a variable and optionally terminates the script.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$var</code> (mixed): The variable to dump.</li>
            <li><code>$die</code> (bool): Whether to terminate the script.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>dump($var, true);</code></pre>
    </div>

    <div class="function-section">
        <h3>d($var, $die = false)</h3>
        <p>Alias for <code>dump</code> method.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$var</code> (mixed): The variable to dump.</li>
            <li><code>$die</code> (bool): Whether to terminate the script.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>d($var, true);</code></pre>
    </div>

    <div class="function-section">
        <h3>dd($var)</h3>
        <p>Alias For "Dump and Die". Dumps a variable and terminates the script.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$var</code> (mixed): The variable to dump.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>dd($var);</code></pre>
    </div>

    <div class="function-section">
        <h3>strand($length = 10)</h3>
        <p>Generates a random alphanumeric string of a specified length.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$length</code> (int): The length of the random string.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>strand(8); // Output: "9S34zD7o"</code></pre>
    </div>

    <div class="function-section">
        <h3>post($url, $data)</h3>
        <p>Sends a POST <code>application/x-www-form-urlencoded</code> request with form data.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$url</code> (string): The URL to send the request to.</li>
            <li><code>$data</code> (array): The form data to send.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>post('https://example.com/api/data', ['key' => 'value']);</code></pre>
    </div>

    <div class="function-section">
        <h3>post_json($url, $data)</h3>
        <p>Sends a POST <code>application/json</code> request with JSON data.</p>
        <p><strong>Parameters:</strong></p>
        <ul>
            <li><code>$url</code> (string): The URL to send the request to.</li>
            <li><code>$data</code> (array): The JSON data to send.</li>
        </ul>
        <p><strong>Example Usage:</strong></p>
        <pre><code>post_json('https://example.com/api/data', ['key' => 'value']);</code></pre>
    </div>

    <div class="function-section">
        <h3>backtrace()</h3>
        <p>Outputs a backtrace of the current call stack.</p>
        <p><strong>Example Usage:</strong></p>
        <pre><code>backtrace(); // Outputs a backtrace of the current call stack.</code></pre>
    </div>

    <div class="function-section">
        <h3>close_everything()</h3>
        <p>Flushes the output buffer and closes the connections.</p>
        <p><strong>Example Usage:</strong></p>
        <pre><code>close_everything();</code></pre>
    </div>

    <div class="function-section">
        <h3>die_gracefully()</h3>
        <p>Closes everything and terminates the script.</p>
        <p><strong>Example Usage:</strong></p>
        <pre><code>die_gracefully();</code></pre>
    </div>
</div>
<h2>Documentation</h2>
<p>You can find the documentation <a href="https://github.com/pwwiur/ais/wiki" target="_blank">here</a>.</p>
