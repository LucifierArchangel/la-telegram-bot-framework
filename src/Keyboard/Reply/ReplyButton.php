<?php

    namespace Lucifier\Framework\Keyboard\Reply;

    /**
     * Simple reply button constructor
     */
    class ReplyButton {
        /**
         * @var mixed|string reply button text
         */
        protected string $text;

        /**
         * @param $text string reply button string
         */
        public function __construct(string $text="Reply Button Example") {
            $this->text = $text;
        }

        /**
         * @return array|string[] button array view
         */
        public function build(): array {
            return ['text' => $this->text];
        }
    }

?>