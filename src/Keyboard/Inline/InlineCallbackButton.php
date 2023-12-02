<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    /**
     * Simple callback inline button
     */
    class InlineCallbackButton {
        /**
         * @var string inline button text
         */
        private string $text;

        /**
         * @var string inline button callback
         */
        private string $callback;

        /**
         * @param string $text      inline button text
         * @param string $callback  inline button callback
         */
        public function __construct(string $text, string $callback) {
            $this->text = $text;
            $this->callback = $callback;
        }

        /**
         * Build button array
         * @return array
         */
        public function build(): array {
            return ['text' => $this->text, 'callback' => $this->callback];
        }
    }

?>