<?php

    namespace Lucifier\Framework\Keyboard\Reply;

    /**
     * Simple reply keyboard constructor
     */
    class ReplyKeyboard {
        /**
         * @var array array of reply buttons rows
         */
        private array $rows;

        /**
         * Constructor
         */
        public function __construct() {
            $this->rows = array();
        }

        /**
         * Add new row for reply keyboard
         * @return $this
         */
        public function addRow(): static {
            $this->rows[] = new ReplyRow();

            return $this;
        }

        /**
         * Add new reply button for current keyboard's row
         * @param string $text reply button text
         * @return $this
         */
        public function addButton(string $text="Reply Button Example"): static {
            $this->rows[count($this->rows) - 1]->addButton($text);

            return $this;
        }

        /**
         * Build keyboard array
         * @return array
         */
        public function build(): array {
            $result = array();

            foreach ($this->rows as $row) {
                $result[] = $row->build();
            }

            return $result;
        }
    }

?>