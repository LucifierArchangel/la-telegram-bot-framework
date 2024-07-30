<?php

namespace Lucifier\Framework\Core\Bot;

use Lucifier\Framework\Core\BotRouter\BotRouter;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Update;

class Bot
{
    /**
     * @var string bot's prefix
     */
    protected string $prefix;

    /**
     * @var string bot's token
     */
    protected string $token;

    /**
     * @var Client bot's instance
     */
    protected $client;

    /**
     * @var BotRouter bot's router
     */
    protected BotRouter $router;

    /**
     * @var ?int bot id
     */
    protected ?int $botId;

    public function __construct()
    {
        $this->router = new BotRouter();
    }

    public function setBotId(?int $botId):void
    {
        $this->botId = $botId;
    }

    /**
     * Bot initialization
     *
     * @return void
     */
    public function initClient(): void
    {
        $this->client = new Client($this->token);

        $bot = $this->client;

        $this->client->on(function (Update $update) use ($bot) {
            $this->router->handle($update, $bot);
        }, function () {
            return true;
        });
    }

    /**
     * Set bot token
     *
     * @param string $token bot's token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Get current bot's prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @throws InvalidJsonException
     */
    public function run(): void
    {
        $this->client->run();
    }
}
