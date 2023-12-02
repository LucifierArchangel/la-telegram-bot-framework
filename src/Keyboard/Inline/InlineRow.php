<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    /**
     * Simple inline row constructor
     */
    class InlineRow {
        /**
         * @var array array of inline buttons
         */
        private array $buttons;

        /**
         * Constructor
         */
        public function __construct() {
            $this->buttons = array();
        }

        /**
         * Add new inline button for current string
         * @param string $type   inline button type, maybe "inline" or "url"
         * @param string $text   inline button text
         * @param string $data   inline button data (callback string or url address)
         * @return $this
         */
        public function addButton(string $type="inline", string $text="Example Text", string $data=""): static {
            if ($type == "inline") {
                $this->buttons[] = new InlineCallbackButton($text, $data);
            } else if ($type == "url") {
                $this->buttons[] = new InlineUrlButton($text, $data);
            }

            return $this;
        }

        /**
         * Build inline buttons row
         * @return array
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