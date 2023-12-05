<?php

    namespace Lucifier\Framework\Callback;

    class Callback {
        /**
         * @var mixed|string callback's name
         */
        private $name;

        /**
         * @var array parameters for current callback
         */
        private $parameters = array();

        public function __construct($name="callback") {
            $this->name = $name;
        }

        /**
         * Add new parameter for callback
         *
         * @param string $name     parameter name
         * @param string $value    parameter value
         * @return $this
         */
        public function addParameter(string $name, string $value): Callback {
            $this->parameters[] = new CallbackParameter($name, $value);

            return $this;
        }

        /**
         * Build callback result string
         *
         * @return string
         */
        public function build(): string {
            $result = $this->name;

            foreach ($this->parameters as $parameter) {
                $result = $result."&".$parameter->build();
            }

            return $result;
        }
    }

?>