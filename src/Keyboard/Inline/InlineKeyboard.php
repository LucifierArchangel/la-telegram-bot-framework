<?php

    namespace Lucifier\Framework\Keyboard\Inline;

    class InlineKeyboard {
        private $rows;

        public function __construct() {
            $this->rows = array();
        }

        public function addRow() {
            array_push($this->rows, new InlineRow());

            return $this;
        }

        public function addButton($type="inline", $text="Example Text", $data="") {
            $this->rows[count($this->rows) - 1]->addButton($type, $text, $data);

            return $this;
        }

        public function build() {
            $result = array();

            foreach ($this->rows as $row) {
                array_push($result, $row->build());
            }

            return $result;
        }
    }

?>