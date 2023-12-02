<?php

    namespace Lucifier\Framework\Message;

    class Message {
        public $fields = array();
        public $template = "";

        private function checkData($data) {
            foreach ($this->fields as $field) {
                if (!array_key_exists($field, $data)) {
                    return false;
                }
            }

            return true;
        }

        private function compile($data) {
            preg_match_all('~\{{\s*(.+?)\s*\}}~is', $this->template, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $this->template = str_replace($match[0], $data[$match[1]], $this->template);
            }
        }

        public function run($data=array()) {
            if ($this->checkData($data)) {
                $this->compile($data);

                echo $this->template;
            }
        }
    }

?>