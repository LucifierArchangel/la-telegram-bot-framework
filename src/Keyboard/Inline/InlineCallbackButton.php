<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    class InlineCallbackButton {
        private $text;
        private $callback;

        public function __construct($text, $callback) {
            $this->text = $text;
            $this->callback = $callback;
        }

        public function build() {
            return ['text' => $this->text, 'callback' => $this->callback];
        }
    }

?>