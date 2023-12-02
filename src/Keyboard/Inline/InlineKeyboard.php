<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    /**
     * Simple inline keyboard constructor
     */
    class InlineKeyboard {
        /**
         * @var array array of inline button's rows
         */
        private array $rows;

        /**
         * Constructor
         */
        public function __construct() {
            $this->rows = array();
        }

        /**
         * Add new row for keyboard
         * @return $this
         */
        public function addRow(): static {
            $this->rows[] = new InlineRow();

            return $this;
        }

        /**
         * Add new button for current row
         * @param string $type   inline button type, maybe "inline" or "url"
         * @param string $text   inline button text
         * @param string $data   inline button data (callback string or url address)
         * @return $this
         */
        public function addButton(string $type="inline", string $text="Example Text", string $data=""): static {
            $this->rows[count($this->rows) - 1]->addButton($type, $text, $data);

            return $this;
        }

        public function build(): array {
            $result = array();

            foreach ($this->rows as $row) {
                $result[] = $row->build();
            }

            return $result;
        }
    }

?>