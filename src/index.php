<?php
    require_once __DIR__."/../vendor/autoload.php";

    $test = "/er/";
    $t = "123er123er";

    preg_match($test, $t, $matches);

    var_dump($matches);
?>