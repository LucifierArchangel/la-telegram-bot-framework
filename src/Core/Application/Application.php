<?php

    namespace Lucifier\Framework\Core\Application;

    use Lucifier\Framework\Core\Bot\Bot;

    class Application {
        /**
         * @var Application|null application static instance
         */
        private static Application|null $instance = null;

        /**
         * @var array application's bots array
         */
        private static array $bots = [];

        private function __construct() {}

        protected function __clone() {}

        /**
         * @return Application get application singleton instance
         */
        public static function instance(): Application {
            if(is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Add new bot for application
         *
         * @param Bot $bot bot
         * @return void
         */
        public function addBot(Bot $bot): void {
            self::$bots[] = $bot;
        }

        /**
         * Return application's bots array
         *
         * @return array
         */
        public function getBots(): array {
            return self::$bots;
        }

        /**
         * Get bot by bot's prefix
         *
         * @param string $prefix
         * @return Bot|null
         */
        public function getBotByPrefix(string $prefix): Bot|null {
            foreach (self::$bots as $bot) {
                if ($bot->getPrefix() === $prefix) return $bot;
            }

            return null;
        }
    }

?>