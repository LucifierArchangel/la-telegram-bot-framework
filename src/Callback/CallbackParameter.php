<?php

    namespace Lucifier\Framework\Callback;

    class CallbackParameter {
        private $name;
        private $value;

        public function __construct(string $name, $value) {
            $this->name = $name;
            $this->value = $value;
        }

        public function build(): string {
            return $this->name."=".$this->value;
        }
    }

?>