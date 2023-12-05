<?php
    require_once __DIR__."/../vendor/autoload.php";

    use Lucifier\Framework\Core\DIContainer\DIContainer;

    $instance = DIContainer::instance();

    $instance->setNamespace("Lucifier\\Framework\\Test\\");

    try {
        $instance->call("Test@run", [
            "name" => "Alex"
        ]);
    } catch (ReflectionException $e) {
    }

?>