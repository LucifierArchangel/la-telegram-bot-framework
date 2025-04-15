<?php

namespace Lucifier\Framework\Core\Bot;

use Lucifier\Framework\Core\BotRouter\BotRouter;
use TelegramBot\Api\Client;
use TelegramBot\Api\HttpException;
use TelegramBot\Api\Types\Update;
use Exception;

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

    /**
     * @var ?Update $update
     */
    protected static ?Update $update = null;

    /**
     * @var int $maxRetries
     */
    private int $maxRetries = 3;


    public function __construct(int $botId)
    {
        $this->router = new BotRouter($botId);
    }

    public function setBotId(?int $botId): void
    {
        $this->botId = $botId;
    }

    public static function initializeUpdate(): ?Update
    {
        self::$update = Update::fromResponse(json_decode(file_get_contents('php://input'), true));
        return self::$update;
    }

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

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function processUpdate(?Update $update = null): bool
    {
        if (!$update && !self::$update) {
            return false;
        }

        $updateObj = $update ?? self::$update;

        try {
            $this->router->handle($updateObj, $this->client);
            return true;
        } catch (Exception $e) {
            error_log('Failed to process update: ' . $e->getMessage());
            return false;
        }
    }

    private function sendQuickResponse(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            flush();
        }
    }

    private function processUpdateWithRetries(): void
    {
        $retryCount = 0;

        while ($retryCount < $this->maxRetries) {
            try {
                $this->processUpdate();
                break;
            } catch (HttpException $httpException) {
                if (
                    str_contains($httpException->getMessage(), 'Could not resolve host')
                    || str_contains($httpException->getMessage(), 'Failed to connect to')
                ) {
                    $retryCount++;
                    if ($retryCount >= $this->maxRetries) {
                        error_log("Failed to resolve host after {$this->maxRetries} attempts: " . $httpException->getMessage());
                        break;
                    }
                    sleep(1);
                } else {
                    error_log("HTTP Exception: " . $httpException->getMessage());
                    break;
                }
            } catch (Exception $e) {
                error_log("Exception in processUpdate: " . $e->getMessage());
                break;
            }
        }
    }

    public function run(): void
    {
        $this->sendQuickResponse();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            $this->processUpdateWithRetries();
        } else {
            $this->processUpdateWithRetries();
        }
    }
}
