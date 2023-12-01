<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    class InlineUrlButton {
        private $text;
        private $url;

        public function __construct($text, $url) {
            $this->text = $text;
            $this->url = $url;
        }

        public function build() {
            return ['text' => $this->text, 'url' => $this->url];
        }
    }

?>