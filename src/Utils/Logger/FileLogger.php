<?php

    namespace Lucifier\Framework\Utils\Logger;

    use Exception;

    class FileLogger implements ILogger {

        public static function log($info) {
            try {
                file_put_contents("data.txt", print_r($info."\n", true), FILE_APPEND);
            } catch (Exception $err) {}
        }
    }

?>