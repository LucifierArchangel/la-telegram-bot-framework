<?php

    namespace Lucifier\Framework\Keyboard\Reply;

    class ReplyRow {
        private $buttons;

        public function __construct() {
            $this->buttons = array();
        }

        public function addButton($text="Reply Button Example") {
            array_push($this->buttons, new ReplyButton($text));

            return $this;
        }

        public function build() {
            $result = array();
            foreach ($this->buttons as $button) {
                array_push($result, $button->build());
            }

            return $result;
        }
    }

?>