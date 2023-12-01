<?php

    namespace Lucifier\Framework\Keyboard\Reply;

    class ReplyKeyboard {
        private $rows;

        public function __construct() {
            $this->rows = array();
        }

        public function addRow() {
            array_push($this->rows, new ReplyRow());

            return $this;
        }

        public function addButton($text="Reply Button Example") {
            $this->rows[count($this->rows) - 1]->addButton($text);

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