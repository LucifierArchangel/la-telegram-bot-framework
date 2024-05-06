<?php

namespace Lucifier\Framework\Core\BotRouter;

use Lucifier\Framework\Core\IoC\Container;
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
    public function text(string $text, array $action): void {
        $this->routers[] = array(
            "type" => "text",
            "text" => $text,
            "action" => [$action[0], $action[1] ?? "__invoke"]
        );
    }

    /**
     * Add new text handler
     *
     * @param string $action    handler for text action
     * @return void
     */
    public function media(array $action): void {
        $this->routers[] = array(
            "type" => "media",
            "action" => [$action[0], $action[1] ?? "__invoke"]
        );
    }

    /**
     * Add new callback handler
     *
     * @param string $callback   callback's string
     * @param string $action     handler for callback
     * @return void
     */
    public function callback(string $callback, array $action): void {
        $this->routers[] = array(
            "type" => "callback",
            "text" => $callback,
            "action" => [$action[0], $action[1] ?? "__invoke"]
        );
    }

    /**
     * Add new command handler
     *
     * @param string $command   command's string
     * @param string $action    handler for command
     * @return void
     */
    public function command(string $command, array $action): void {
        $this->routers[] = array(
            "type" => "command",
            "text" => $command,
            "action" => [$action[0], $action[1] ?? "__invoke"]
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
     * Get callback string without parameters
     *
     * @param string $data callback data from update
     * @return string
     */
    private function getCallbackWithoutParameters(string $data): string {
        $splitedData = explode("&", $data);

        return $splitedData[0];
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
        $instance = Container::instance();

        $message = $update->getMessage();
        $callback = $update->getCallbackQuery();

        $type = isset($message) ? "text" : "callback";

        if ($type === "text") {
            $type = $this->isCommandMessage($message) ? "command" : "text";
        }

        $data = "";
        if (isset($message)) {
            $data = $message->getText();
            if (isset($data) && !empty($data)) {
                $data = str_replace("/", "", $data);
            } else {
                $type = 'media';
            }
        } else if (isset($callback)) {
            $data = $this->getCallbackWithoutParameters($callback->getData());
        }

        foreach ($this->routers as $router) {
            if ($type === 'text') {
                $matcher = "/".$router["text"]."/";
                preg_match($matcher, $data, $matches, PREG_OFFSET_CAPTURE);

                if (count($matches) !== 0) {
                    $controllerInstance = $instance->resolve($router["action"][0], [
                        "bot" => $bot,
                        "update" => $update
                    ]);
                    $instance->resolveMethod($controllerInstance, $router["action"][1], [
                        "bot" => $bot,
                        "update" => $update
                    ]);

                    break;
                }

            } else if ($type === 'callback' || $type === 'command'){
                if ($router["type"] === $type) {
                    if ($data === $router["text"]) {
                        $controllerInstance = $instance->resolve($router["action"][0], [
                            "bot" => $bot,
                            "update" => $update,
                        ]);
                        $instance->resolveMethod($controllerInstance, $router["action"][1], [
                            "bot" => $bot,
                            "update" => $update,
                        ]);

                        break;
                    }
                }

            } else if ($router["type"] === 'media') {
                $controllerInstance = $instance->resolve($router["action"][0], [
                    "bot" => $bot,
                    "update" => $update
                ]);
                $instance->resolveMethod($controllerInstance, $router["action"][1], [
                    "bot" => $bot,
                    "update" => $update
                ]);

                break;
            }
        }
    }
}
?>