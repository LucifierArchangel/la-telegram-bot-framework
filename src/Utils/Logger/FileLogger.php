<?php

    namespace Lucifier\Framework\Utils\Logger;

    use Exception;

    class FileLogger implements ILogger {

        /**
         * Log mixed type data to "data.txt" file
         *
         * @param mixed $info
         * @return void
         */
        public static function log(mixed $info) {
            try {
                file_put_contents("data.txt", print_r($info."\n", true), FILE_APPEND);
            } catch (Exception $err) {}
        }
    }

?>