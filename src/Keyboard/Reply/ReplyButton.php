<?php

    namespace Lucifier\Framework\Keyboard\Reply;

    class ReplyButton {
        protected $text;

        public function __construct($text="Reply Button Example") {
            $this->text = $text;
        }

        public function build() {
            return ['text' => $this->text];
        }
    }

?>