<?php
    $current_date = time::date();
    view("home", ["current_date" => $current_date], ["title" => CMS_NAME . " v" . CMS_VERSION]);

