<?php

    namespace Lucifier\Framework\Message;

    /**
     * Simple message builder with simple template engine
     */
    abstract class Message {
        /**
         * @var array array of field's names for template engine
         */
        private $fields = array();

        /**
         * @var string message template
         */
        private $template = "";

        /**
         * Validating data against specified fields
         * @param $data array key:value array of data for message templating
         * @return bool       validation result
         */
        private function checkData(array $data): bool {
            foreach ($this->fields as $field) {
                if (!array_key_exists($field, $data)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Compile template
         * @param $data array key:value array of data for message templating
         * @return void
         */
        private function compile(array $data): void {
            preg_match_all('~\{{\s*(.+?)\s*\}}~is', $this->template, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $this->template = str_replace($match[0], $data[$match[1]], $this->template);
            }
        }

        /**
         * Data validation and template compilation
         * @param $data array key:value array of data for message templating
         * @return string     compilation result
         */
        public function run(array $data=array()): string {
            if ($this->checkData($data)) {
                $this->compile($data);

                return $this->template;
            }

            return "";
        }
    }

?>