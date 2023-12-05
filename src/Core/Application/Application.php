<?php

    namespace Lucifier\Framework\Core\Application;

    use Lucifier\Framework\Core\Bot\Bot;

    class Application {
        private static $instance = null;
        private static array $bots = [];

        private function __construct() {
        }
        protected function __clone() {
        }
        public static function instance(): Application {
            if(is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }


        public function addBot(Bot $bot): void {
            self::$bots[] = $bot;
        }

        public function getBots(): array {
            return self::$bots;
        }

        public function getBotByPrefix($prefix): Bot|null {
            foreach (self::$bots as $bot) {
                if ($bot->getPrefix() === $prefix) return $bot;
            }

            return null;
        }
    }

?>