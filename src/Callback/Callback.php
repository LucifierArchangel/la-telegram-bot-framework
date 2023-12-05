<?php

    namespace Lucifier\Framework\Callback;

    class Callback {
        private $name;
        private $parameters = array();

        public function __construct($name="callback") {
            $this->name = $name;
        }

        public function addParameter(string $name, $value): Callback {
            $this->parameters[] = new CallbackParameter($name, $value);

            return $this;
        }

        public function build(): string {
            $result = $this->name;

            foreach ($this->parameters as $parameter) {
                $result = $result."&".$parameter->build();
            }

            return $result;
        }
    }

?>