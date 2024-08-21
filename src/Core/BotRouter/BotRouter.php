<?php
namespace Lucifier\Framework\Core\BotRouter;

use Lucifier\Framework\Core\IoC\Container;
use Lucifier\Framework\Core\Middleware\Middleware;
use ReflectionException;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use Dotenv\Dotenv;

class BotRouter extends Middleware
{
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
        'pre_checkout_query' => [],
        'my_chat_member'     => [],
        'contact'            => []
    ];

    protected array $currentRouter = [];

    /**
     * @var string bot's namespace
     */
    private string $namespace = '';

    /**
     * @var string bot's high level namespace
     */
    private string $originalNamespace;

    private int|string $chatId;

    private \Predis\Client $redis;

    private int $ttl = 86400;

    private int $botId;

    public function __construct(int $botId)
    {
        parent::__construct();

        $this->setRedisClient();
        $this->setBotId($botId);
        $this->loadRoutersFromCache();
    }

    public function setRedisClient(): void
    {
        $this->redis = new \Predis\Client([
            'scheme' => $_ENV['REDIS_SCHEME'],
            'host'   => $_ENV['REDIS_HOST'],
            'port'   => $_ENV['REDIS_PORT'],
        ]);
    }

    public function setBotId(int $botId): void
    {
        $this->botId = $botId;
    }

    private function getBotId(): int
    {
        return $this->botId;
    }

    /**
     * @throws \JsonException
     */
    private function loadRoutersFromCache(): void
    {
        $cacheKey = "bot_routers_{$this->botId}";

        $cachedRouters = $this->redis->get($cacheKey);

        if ($cachedRouters) {
            $this->compiledRouters = json_decode($cachedRouters, true, 512, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @throws \JsonException
     */
    private function saveRoutersToCache(): void
    {
        $cacheKey = "bot_routers_{$this->botId}";

        $this->redis->setex($cacheKey, $this->ttl, json_encode($this->compiledRouters, JSON_THROW_ON_ERROR));
    }

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
     * @return self
     */
    public function text(string $text, array $action): self
    {
        $this->currentRouter = [
            'type'       => 'text',
            'text'       => $text,
            'action'     => [$action[0], $action[1] ?? '__invoke'],
            'middleware' => []
        ];
        $this->addRouter(
            $this->currentRouter['type'],
            $this->currentRouter['text'],
            $this->currentRouter['action'],
            $this->currentRouter['middleware']
        );
        return $this;
    }

    /**
     * Add middleware to the current router
     *
     * @param string $middleware middleware class name
     * @return self
     */
    public function middleware(string $middleware): self
    {
        $this->currentRouter['middleware'][] = [Middleware::class, $middleware];
        $this->updateRouterWithMiddleware();
        return $this;
    }

    /**
     * Update router with middleware
     *
     * @return void
     */
    private function updateRouterWithMiddleware(): void
    {
        foreach ($this->routers as &$router) {
            if (
                $router['type'] === $this->currentRouter['type']
                && $router['text'] === $this->currentRouter['text']
            ) {
                $router['middleware'] = $this->currentRouter['middleware'];
            }
        }

        foreach ($this->compiledRouters['text'] as &$compiledRouter) {
            if ($compiledRouter['pattern'] === '/' . $this->currentRouter['text'] . '/') {
                $compiledRouter['middleware'] = $this->currentRouter['middleware'];
            }
        }
    }

    /**
     * Add new contact handler
     *
     * @param array $action handler for contact action
     * @return void
     */
    public function contact(array $action): void
    {
        $this->addRouter('contact', '', $action);
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
     * Extract command arguments from command text
     *
     * @param string $commandText command text
     * @return array
     */
    private function extractCommandArguments(string $commandText): array
    {
        $parts = explode(' ', $commandText);
        array_shift($parts);
        return $parts;
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
     * Add new myChatMember handler
     *
     * @param array $action handler for preCheckoutQuery action
     * @return void
     */
    public function myChatMember(array $action): void
    {
        $this->addRouter('my_chat_member', '', $action);
    }

    /**
     * Registers a group of routes if the specified condition is met.
     *
     * This method checks the provided condition, and if it returns `true`,
     * it executes the given routes callback, allowing you to group routes
     * under a specific condition (e.g., based on chat type, user permissions, etc.).
     *
     * @param callable $condition A callable that returns a boolean. If `true`, the routes are registered.
     * @param callable $routes A callable that contains the route definitions to be registered if the condition is met.
     *
     * @return void
     */
    public function group(callable $condition, callable $routes): void
    {
        if ($condition()) {
            $routes($this);
        }
    }

    /**
     * Add new router and compile it
     *
     * @param string $type   router type
     * @param string $text   router text or callback string
     * @param array  $action handler action
     * @return void
     */
    private function addRouter(string $type, string $text, array $action, array $middleware = []): void
    {
        $router = [
            'type'       => $type,
            'text'       => $text,
            'action'     => [$action[0], $action[1] ?? '__invoke'],
            'middleware' => $middleware
        ];
        $this->routers[] = $router;

        if ($type === 'text') {
            $this->compiledRouters['text'][] = [
                'pattern'    => '/' . $text . '/',
                'action'     => $router['action'],
                'middleware' => $middleware
            ];
        } else {
            $this->compiledRouters[$type][$text] = $router['action'];
        }

        $this->saveRoutersToCache();
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
        if (!empty($update->getMessage())) {
            $this->setChatIdFromUpdate($update);

            $botId = $bot->getMe()->getId();

            if ($this->isBannedBot($botId)) {
                $bot->sendMessage($this->chatId, 'Бот заблокированы ❌', 'HTML', false, null);
                return;
            }

            if ($this->isBanned($this->chatId)) {
                $bot->sendMessage($this->chatId, 'Вы заблокированы ❌', 'HTML', false, null);
                return;
            }
        }

        $type = $this->determineUpdateType($update);
        $data = $this->extractDataFromUpdate($update, $type);

        $this->processUpdate($instance, $bot, $update, $type, $data);
    }

    /**
     * Determine update type
     *
     * @param Update $update current update instance
     * @return string update type
     */
    private function determineUpdateType(Update $update): string
    {
        $message = $update->getMessage();
        $callback = $update->getCallbackQuery();
        $preCheckoutQuery = $update->getPreCheckoutQuery();
        $myChatMember = $update->getMyChatMember();

        return $this->determineType($message, $callback, $preCheckoutQuery, $myChatMember);
    }

    /**
     * Extract data from update based on type
     *
     * @param Update $update current update instance
     * @param string $type update type
     * @return mixed extracted data
     */
    private function extractDataFromUpdate(Update $update, string $type)
    {
        $message = $update->getMessage();
        $callback = $update->getCallbackQuery();
        return $this->extractData($message, $callback, $type);
    }

    /**
     * Process update based on type
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @param string $type update type
     * @param mixed $data extracted data from update
     * @return void
     */
    private function processUpdate(mixed $instance, Client $bot, Update $update, string $type, mixed $data): void
    {
        switch ($type) {
            case 'text':
                $this->processTextUpdate($instance, $bot, $update, $data);
                break;
            case 'contact':
                $this->processContactUpdate($instance, $bot, $update);
                break;
            case 'my_chat_member':
                $this->processMyChatMemberUpdate($instance, $bot, $update);
                break;
            case 'media':
                $this->processMediaUpdate($instance, $bot, $update);
                break;
            case 'command':
                $this->processCommandUpdate($instance, $bot, $update, $data);
                break;
            default:
                $this->processDefaultUpdate($instance, $bot, $update, $type, $data);
                break;
        }
    }

    /**
     * Process text update
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @param mixed $data extracted data from update
     * @return void
     */
    private function processTextUpdate(mixed $instance, Client $bot, Update $update, mixed $data): void
    {
        foreach ($this->compiledRouters['text'] as $router) {
            if (preg_match($router['pattern'], $data)) {
                if ($this->executeMiddleware($router['middleware'], $bot, $update)) {
                    $this->executeRoute($instance, $router, $bot, $update);
                }
                break;
            }
        }
    }

    /**
     * Execute middleware
     *
     * @param array $middleware list of middleware
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @return bool
     */
    private function executeMiddleware(array $middleware, Client $bot, Update $update): bool
    {
        $chatId = $update->getMessage()->getChat()->getId();
        foreach ($middleware as $middlewareItem) {
            [$middlewareClass, $method] = $middlewareItem;
            $middlewareInstance = new $middlewareClass();
            if (!$middlewareInstance->$method($chatId)) {
                $bot->sendMessage($chatId, 'Access denied ❌', 'HTML', false, null);
                return false;
            }
        }
        return true;
    }

    /**
     * Process contact update
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @return void
     */
    private function processContactUpdate(mixed $instance, Client $bot, Update $update): void
    {
        if (!empty($this->compiledRouters['contact'])) {
            $this->executeRoute($instance, [
                'action' => $this->compiledRouters['contact']['']
            ], $bot, $update);
        }
    }

    /**
     * Process myChatMember update
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @return void
     */
    private function processMyChatMemberUpdate(mixed $instance, Client $bot, Update $update): void
    {
        if (!empty($this->compiledRouters['my_chat_member'])) {
            $this->executeRoute($instance, [
                'action' => $this->compiledRouters['my_chat_member']['']
            ], $bot, $update);
        }
    }

    /**
     * Process media update
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @return void
     */
    private function processMediaUpdate(mixed $instance, Client $bot, Update $update): void
    {
        if (!empty($this->compiledRouters['media'])) {
            $this->executeRoute($instance, [
                'action' => $this->compiledRouters['media']['']
            ], $bot, $update);
        }
    }

    /**
     * Process command update
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @param string $data extracted data from update
     * @return void
     */
    private function processCommandUpdate(mixed $instance, Client $bot, Update $update, string $data): void
    {
        if (strpos($data, 'start') === 0) {
            $arguments = $this->extractCommandArguments($data);
            if (isset($this->compiledRouters['command']['start'])) {
                $this->executeRoute($instance, [
                    'action' => $this->compiledRouters['command']['start']
                ], $bot, $update, $arguments);
            }
        }
    }

    /**
     * Process default update
     *
     * @param mixed $instance instance of the container
     * @param Client $bot current bot instance
     * @param Update $update current update instance
     * @param string $type update type
     * @param mixed $data extracted data from update
     * @return void
     */
    private function processDefaultUpdate(mixed $instance, Client $bot, Update $update, string $type, mixed $data): void
    {
        if (isset($this->compiledRouters[$type][$data])) {
            $this->executeRoute($instance, [
                'action' => $this->compiledRouters[$type][$data]
            ], $bot, $update);
        }
    }


    /**
     * Set chatId from Update
     *
     * @param Update $update
     * @return void
     */
    protected function setChatIdFromUpdate(Update $update): void
    {
        if ($update->getMessage()) {
            $this->chatId = $update->getMessage()->getChat()->getId();
        } elseif ($update->getCallbackQuery()) {
            $this->chatId = $update->getCallbackQuery()->getMessage()->getChat()->getId();
        } elseif ($update->getEditedMessage()) {
            $this->chatId = $update->getEditedMessage()->getChat()->getId();
        } elseif ($update->getChannelPost()) {
            $this->chatId = $update->getChannelPost()->getChat()->getId();
        } elseif ($update->getEditedChannelPost()) {
            $this->chatId = $update->getEditedChannelPost()->getChat()->getId();
        } elseif ($update->getInlineQuery()) {
            $this->chatId = $update->getInlineQuery()->getFrom()->getId();
        } elseif ($update->getChosenInlineResult()) {
            $this->chatId = $update->getChosenInlineResult()->getFrom()->getId();
        } elseif ($update->getShippingQuery()) {
            $this->chatId = $update->getShippingQuery()->getFrom()->getId();
        } elseif ($update->getPreCheckoutQuery()) {
            $this->chatId = $update->getPreCheckoutQuery()->getFrom()->getId();
        } elseif ($update->getPollAnswer()) {
            $this->chatId = $update->getPollAnswer()->getUser()->getId();
        } else {
            $this->chatId = null;
        }
    }

    private function determineType($message, $callback, $preCheckoutQuery, $myChatMember): string
    {
        if ($preCheckoutQuery) {
            return 'pre_checkout_query';
        }
        if ($myChatMember) {
            return 'my_chat_member';
        }
        if ($message) {
            if ($message->getContact()) {
                return 'contact';
            }
            if ($message->getPhoto() || $message->getVideo() || $message->getDocument()) {
                return 'media';
            }
            return $this->isCommandMessage($message) ? 'command' : 'text';
        }
        return 'callback';
    }

    private function extractData($message, $callback, $type): string
    {
        if ($type === 'text' || $type === 'command') {
            $data = $message->getText();
            if ($data) {
                $command = str_replace('/', '', $data);
                return $command;
            }
            return '';
        }
        if ($type === 'callback') {
            return $this->getCallbackWithoutParameters($callback->getData());
        }
        if ($type === 'contact') {
            return $message->getContact()->getPhoneNumber();
        }
        if ($type === 'media') {
            if ($message->getPhoto()) {
                return 'photo';
            }
            if ($message->getVideo()) {
                return 'video';
            }
            if ($message->getDocument()) {
                return 'document';
            }
        }
        return '';
    }

    private function executeRoute(
        $instance,
        $router,
        $bot,
        $update,
        $arguments = []
    ): void {
        $controllerInstance = $instance->resolve($router['action'][0], [
            'bot'       => $bot,
            'update'    => $update,
            'arguments' => $arguments
        ]);
        $instance->resolveMethod($controllerInstance, $router['action'][1], [
            'bot'       => $bot,
            'update'    => $update,
            'arguments' => $arguments
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
