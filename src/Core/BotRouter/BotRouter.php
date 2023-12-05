<?php

    namespace Lucifier\Framework\Core\BotRouter;

    use Lucifier\Framework\Core\DIContainer\DIContainer;
    use ReflectionException;
    use TelegramBot\Api\Client;
    use TelegramBot\Api\Types\Message;
    use TelegramBot\Api\Types\Update;

    class BotRouter {
        /**
         * @var array bot's routers array
         */
        private array $routers = [];

        /**
         * @var string bot's namespace
         */
        private string $namespace = "";

        /**
         * @var string bot's high level namespace
         */
        private string $originalNamespace;

        public function __construct() {}

        /**
         * Set namespace for current bot router
         *
         * @param string $namespace bot's high level namespace
         * @return void
         */
        public function setNamespace(string $namespace): void {
            $this->namespace = $namespace."\\Controllers\\";
            $this->originalNamespace = $namespace;
        }

        /**
         * Add new text handler
         *
         * @param string $text      text's string
         * @param string $action    handler for text action
         * @return void
         */
        public function text(string $text, string $action): void {
            $this->routers[] = array(
                "type" => "text",
                "text" => $text,
                "action" => $action
            );
        }

        /**
         * Add new callback handler
         *
         * @param string $callback   callback's string
         * @param string $action     handler for callback
         * @return void
         */
        public function callback(string $callback, string $action): void {
            $this->routers[] = array(
                "type" => "callback",
                "text" => $callback,
                "action" => $action
            );
        }

        /**
         * Add new command handler
         *
         * @param string $command   command's string
         * @param string $action    handler for command
         * @return void
         */
        public function command(string $command, string $action): void {
            $this->routers[] = array(
                "type" => "command",
                "text" => $command,
                "action" => $action
            );
        }

        /**
         * Check message what is text command message
         *
         * @param Message $message current message instance
         * @return bool
         */
        private function isCommandMessage(Message $message): bool {
            $entities = $message->getEntities();

            if (isset($entities)) {
                foreach ($entities as $entity) {
                    if ($entity->getType() === "bot_command") {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Handle bot action with applied handlers
         *
         * @param Update $update current update instance
         * @param Client $bot current bot instance
         * @return void
         * @throws ReflectionException
         */
        public function handle(Update $update, Client $bot): void {
            $instance = DIContainer::instance();
            $instance->setNamespace($this->namespace);

            $message = $update->getMessage();
            $callback = $update->getCallbackQuery();

            $type = isset($message) ? "text" : "callback";

            if ($type === "text") {
                $type = $this->isCommandMessage($message) ? "command" : "text";
            }

            $data = "";
            if (isset($message)) {
                $data = $message->getText();
                $data = str_replace("/", "", $data);
            } else if (isset($callback)) {
                $data = $callback->getData();
            }

            foreach ($this->routers as $router) {
                if ($router["type"] === $type) {
                    if ($data === $router["text"]) {
                        $instance->call($router["action"], [
                            "bot" => $bot,
                            "update" => $update,
                            "namespace" => $this->originalNamespace
                        ]);
                    }
                }
            }
        }
    }
?>