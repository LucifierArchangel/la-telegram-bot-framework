<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    class InlineRow {
        private $buttons;

        public function __construct() {
            $this->buttons = array();
        }

        public function addButton($type="inline", $text="Example Text", $data="") {
            if ($type == "inline") {
                array_push($this->buttons, new InlineCallbackButton($text, $data));
            } else if ($type == "url") {
                array_push($this->buttons, new InlineUrlButton($text, $data));
            }

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