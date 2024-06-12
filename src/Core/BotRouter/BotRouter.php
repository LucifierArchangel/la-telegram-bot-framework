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
     * @var array compiled routers array for quick access
     */
    private array $compiledRouters = [
        'text'               => [],
        'command'            => [],
        'callback'           => [],
        'media'              => [],
        'pre_checkout_query' => []
    ];

    /**
     * @var string bot's namespace
     */
    private string $namespace = '';

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
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace . "\\Controllers\\";
        $this->originalNamespace = $namespace;
    }

    /**
     * Add new text handler
     *
     * @param string $text      text's string
     * @param array  $action    handler for text action
     * @return void
     */
    public function text(string $text, array $action): void
    {
        $this->addRouter('text', $text, $action);
    }

    /**
     * Add new media handler
     *
     * @param array $action handler for media action
     * @return void
     */
    public function media(array $action): void
    {
        $this->addRouter('media', '', $action);
    }

    /**
     * Add new callback handler
     *
     * @param string $callback callback's string
     * @param array  $action   handler for callback
     * @return void
     */
    public function callback(string $callback, array $action): void
    {
        $this->addRouter('callback', $callback, $action);
    }

    /**
     * Add new command handler
     *
     * @param string $command command's string
     * @param array  $action  handler for command
     * @return void
     */
    public function command(string $command, array $action): void
    {
        $this->addRouter('command', $command, $action);
    }

    /**
     * Add new preCheckoutQuery handler
     *
     * @param array $action handler for preCheckoutQuery action
     * @return void
     */
    public function preCheckoutQuery(array $action): void
    {
        $this->addRouter('pre_checkout_query', '', $action);
    }

    /**
     * Add new router and compile it
     *
     * @param string $type   router type
     * @param string $text   router text or callback string
     * @param array  $action handler action
     * @return void
     */
    private function addRouter(string $type, string $text, array $action): void
    {
        $router = [
            'type'   => $type,
            'text'   => $text,
            'action' => [$action[0], $action[1] ?? '__invoke']
        ];
        $this->routers[] = $router;

        if ($type === 'text') {
            $this->compiledRouters['text'][] = [
                'pattern' => '/' . $text . '/',
                'action'  => $router['action']
            ];
        } else {
            $this->compiledRouters[$type][$text] = $router['action'];
        }
    }

    /**
     * Handle bot action with applied handlers
     *
     * @param Update $update current update instance
     * @param Client $bot current bot instance
     * @return void
     * @throws ReflectionException
     */
    public function handle(Update $update, Client $bot): void
    {
        $instance = Container::instance();

        $message = $update->getMessage();
        $callback = $update->getCallbackQuery();
        $preCheckoutQuery = $update->getPreCheckoutQuery();

        $type = $this->determineType($message, $callback, $preCheckoutQuery);

        $data = $this->extractData($message, $callback, $type);

        if ($type === 'text') {
            foreach ($this->compiledRouters['text'] as $router) {
                if (preg_match($router['pattern'], $data)) {
                    $this->executeRoute($instance, $router, $bot, $update);
                    break;
                }
            }
        } else {
            if (isset($this->compiledRouters[$type][$data])) {
                $this->executeRoute($instance, [
                    'action' => $this->compiledRouters[$type][$data]
                ], $bot, $update);
            }
        }
    }

    private function determineType($message, $callback, $preCheckoutQuery): string
    {
        if ($preCheckoutQuery) {
            return 'pre_checkout_query';
        }
        if ($message) {
            return $this->isCommandMessage($message) ? 'command' : 'text';
        }
        return 'callback';
    }

    private function extractData($message, $callback, $type): string
    {
        if ($type === 'text' || $type === 'command') {
            $data = $message->getText();
            return $data ? str_replace('/', '', $data) : '';
        }
        if ($type === 'callback') {
            return $this->getCallbackWithoutParameters($callback->getData());
        }
        return '';
    }

    private function executeRoute(
        $instance,
        $router,
        $bot,
        $update
    ): void {
        $controllerInstance = $instance->resolve($router['action'][0], [
            'bot'    => $bot,
            'update' => $update
        ]);
        $instance->resolveMethod($controllerInstance, $router['action'][1], [
            'bot'    => $bot,
            'update' => $update
        ]);
    }

    /**
     * Check if the message is a command message
     *
     * @param Message $message current message instance
     * @return bool
     */
    private function isCommandMessage(Message $message): bool
    {
        $entities = $message->getEntities();

        if (isset($entities)) {
            foreach ($entities as $entity) {
                if ($entity->getType() === 'bot_command') {
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
    private function getCallbackWithoutParameters(string $data): string
    {
        $splitedData = explode('&', $data);

        return $splitedData[0];
    }
}
