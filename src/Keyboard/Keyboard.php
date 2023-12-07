<?php

    namespace Lucifier\Framework\Keyboard;

    use Lucifier\Framework\Keyboard\Inline\InlineKeyboard;
    use Lucifier\Framework\Keyboard\Reply\ReplyKeyboard;
    use Lucifier\Framework\Utils\Logger\FileLogger;

    class Keyboard {
        /**
         * @var string current keyboard's type. May be "reply" or "inline"
         */
        protected $type = "reply";
        /**
         * @var InlineKeyboard|ReplyKeyboard current keyboard
         */
        protected $keyboard;

        /**
         * Configure current keyboard class
         * For child class
         *
         * @param array $parameters parameters array for keyboard configure
         * @return void
         */
        public function configure (array $parameters=[]) {}

        /**
         * Get current keyboard type
         *
         * @return string
         */
        public function getType(): string {
            return $this->type;
        }

        /**
         * Build current keyboard result object
         *
         * @param array $parameters
         * @return array
         */
        public function build(array $parameters=[]): array {
            $this->configure($parameters);

            return $this->keyboard->build();
        }
    }

?>