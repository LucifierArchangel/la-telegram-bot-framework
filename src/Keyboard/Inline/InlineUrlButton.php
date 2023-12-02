<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    /**
     * Simple url inline button
     */
    class InlineUrlButton {
        /**
         * @var string inline button text
         */
        private string $text;

        /**
         * @var string inline url text
         */
        private string $url;

        /**
         * @param string $text   inline button text
         * @param string $url    inline button url
         */
        public function __construct(string $text, string $url) {
            $this->text = $text;
            $this->url = $url;
        }

        /**
         * Build button array
         * @return array
         */
        public function build(): array {
            return ['text' => $this->text, 'url' => $this->url];
        }
    }

?>