<?php

    namespace Lucifier\Framework\Callback;

    class CallbackParameter {
        /**
         * @var string callback parameter's name
         */
        private string $name;

        /**
         * @var string callback parameter's value
         */
        private string $value;

        public function __construct(string $name, $value) {
            $this->name = $name;
            $this->value = $value;
        }

        /**
         * Build callback parameter result string
         *
         * @return string
         */
        public function build(): string {
            return $this->name."=".$this->value;
        }
    }

?>