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
        'pre_checkout_query' => [],
        'my_chat_member'     => [],
        'contact'            => []
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
        $myChatMember = $update->getMyChatMember();

        $type = $this->determineType($message, $callback, $preCheckoutQuery, $myChatMember);

        $data = $this->extractData($message, $callback, $type);

        if ($type === 'text') {
            foreach ($this->compiledRouters['text'] as $router) {
                if (preg_match($router['pattern'], $data)) {
                    $this->executeRoute($instance, $router, $bot, $update);
                    break;
                }
            }
        } else if ($type === 'contact') {
            if (!empty($this->compiledRouters['contact'])) {
                $this->executeRoute($instance, [
                    'action' => $this->compiledRouters['contact']['']
                ], $bot, $update);
            }
        } else if ($type === 'my_chat_member') {
            if (!empty($this->compiledRouters['my_chat_member'])) {
                $this->executeRoute($instance, [
                    'action' => $this->compiledRouters['my_chat_member']['']
                ], $bot, $update);
            }
        } else if ($type === 'media') {
            if (!empty($this->compiledRouters['media'])) {
                $this->executeRoute($instance, [
                    'action' => $this->compiledRouters['media']['']
                ], $bot, $update);
            }
        } else {
            if (isset($this->compiledRouters[$type][$data])) {
                $this->executeRoute($instance, [
                    'action' => $this->compiledRouters[$type][$data]
                ], $bot, $update);
            } else if ($type === 'command' && strpos($data, 'start') === 0) {
                $arguments = $this->extractCommandArguments($data);
                if (isset($this->compiledRouters[$type]['start'])) {
                    $this->executeRoute($instance, [
                        'action' => $this->compiledRouters[$type]['start']
                    ], $bot, $update, $arguments);
                }
            }
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
