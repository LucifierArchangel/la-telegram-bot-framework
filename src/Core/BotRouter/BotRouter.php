<?php

    namespace Lucifier\Framework\Core\BotRouter;

    use Exception;
    use Lucifier\Framework\Core\DIContainer\DIContainer;
    use TelegramBot\Api\Client;
    use TelegramBot\Api\Types\Message;
    use TelegramBot\Api\Types\Update;

    class BotRouter {
        private array $routers = [];
        private string $namespace = "";

        public function __construct() {}

        public function setNamespace(string $namespace) {
            $this->namespace = $namespace;
        }

        public function text(string $text, string $action) {
            $this->routers[] = array(
                "type" => "text",
                "text" => $text,
                "action" => $action
            );
        }

        public function callback(string $callback, string $action) {
            $this->routers[] = array(
                "type" => "callback",
                "text" => $callback,
                "action" => $action
            );
        }

        public function command(string $command, string $action) {
            $this->routers[] = array(
                "type" => "command",
                "text" => $command,
                "action" => $action
            );
        }

        private function isCommandMessage(Message $message) {
            $entities = $message->getEntities();

            foreach ($entities as $entity) {
                if ($entity->getType() === "bot_command") {
                    return true;
                }
            }

            return false;
        }

        /**
         * @throws \ReflectionException
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
                        try {
                            $instance->call($router["action"], [
                                "bot" => $bot,
                                "update" => $update
                            ]);
                        } catch(Exception $err) {
                        }
                    }
                }
            }
        }
    }
?>