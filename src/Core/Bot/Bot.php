<?php

    namespace Lucifier\Framework\Core\Bot;

    use Lucifier\Framework\Core\BotRouter\BotRouter;
    use Lucifier\Framework\Keyboard\Inline\InlineKeyboard;
    use TelegramBot\Api\Client;
    use TelegramBot\Api\InvalidJsonException;
    use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
    use TelegramBot\Api\Types\Update;

    class Bot {
        protected string $prefix;
        protected string $token;
        protected Client $client;
        protected BotRouter $router;

        public function __construct() {
            $this->router = new BotRouter();
        }

        public function initClient(): void {
            $this->client = new Client($this->token);

            $bot = $this->client;

            $this->client->on(function (Update $update) use($bot) {
                $this->router->handle($update, $bot);
            }, function () {
                return true;
            });
        }

        public function setToken(string $token): void {
            $this->token = $token;
        }

        public function getPrefix(): string {
            return $this->prefix;
        }

        /**
         * @throws InvalidJsonException
         */
        public function run(): void {
            $this->client->run();
        }
    }

?>