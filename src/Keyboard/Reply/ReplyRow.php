<?php

    namespace Lucifier\Framework\Keyboard\Reply;

    /**
     * Simple reply keyboard row abstraction
     */
    class ReplyRow {
        /**
         * @var array array of reply buttons
         */
        private array $buttons;

        /**
         * Constructor
         */
        public function __construct() {
            $this->buttons = array();
        }

        /**
         * @param string $text reply button text
         * @return $this
         */
        public function addButton(string $text="Reply Button Example"): static {
            $this->buttons[] = new ReplyButton($text);

            return $this;
        }

        /**
         * @return array array of reply buttons row
         */
        public function build(): array {
            $result = array();
            foreach ($this->buttons as $button) {
                $result[] = $button->build();
            }

            return $result;
        }
    }

?>