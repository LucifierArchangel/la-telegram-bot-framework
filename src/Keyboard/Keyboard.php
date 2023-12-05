<?php

    namespace Lucifier\Framework\Keyboard;

    class Keyboard {
        protected $type = "reply";
        protected $keyboard;

        public function configure ($parameters=[]) {}

        public function getType() {
            return $this->type;
        }

        public function build($parametes=[]) {
            $this->configure($parametes);

            return $this->keyboard->build($parametes);
        }
    }

?>