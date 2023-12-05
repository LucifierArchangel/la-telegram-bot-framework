<?php

    namespace Lucifier\Framework\Test;

    class Test {

        public function __construct() { }

        public function run($name): void {
            echo "Hello ".$name."\n";
        }
    }

?>