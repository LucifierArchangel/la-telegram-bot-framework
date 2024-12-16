<?php

namespace Lucifier\Framework\Core\Middleware;

use Lucifier\Framework\Core\Database\DB;
use Dotenv\Dotenv;

class Middleware
{
    protected DB $DB;
    protected array $config;

    private const ENV_PATH = __DIR__ . '/../../../../../../';
    private const CONFIG_PATH = __DIR__ . '/../../../config/middlewareConfig.php';

    public function __construct()
    {
        $this->initDb();
        $this->loadConfig();
    }

    private function initDb(): void
    {
        if (file_exists(__DIR__ . '/../../../../../../.env')) {
            $dotenv = Dotenv::createImmutable(paths: self::ENV_PATH);
            $dotenv->load();
        }

        $this->DB = DB::getInstance();
        $this->DB->openNew($_ENV['DB_SERVER'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE'], $_ENV['DB_PORT']);
    }

    private function loadConfig(): void
    {
        $this->config = require self::CONFIG_PATH;
    }

    private function checkCondition(string $type, int|string $chatId, array $extraConditions = []): bool|array
    {
        if (!isset($this->config[$type])) {
            throw new \Exception('Unknown middleware type:' . $type);
        }

        $config = $this->config[$type];
        $table = $config['table'];
        $conditions = $config['conditions'];

        $queryConditions = [];
        foreach (array_merge($conditions, $extraConditions) as $field => $value) {
            $value = ($value === ':chatId') ? $chatId : $value;
            $queryConditions[] = "$field = '$value'";
        }
        $queryConditionString = implode(' AND ', $queryConditions);

        $query = "SELECT * FROM $table WHERE $queryConditionString";

        return $this->DB->getTable($query);
    }

    public function isBanned(int|string $chatId, int|string $botChatId): bool
    {
        error_log("isBanned called with chatId: $chatId, botChatId: $botChatId");

        $query = "SELECT id FROM Bot WHERE chatId = :botChatId";
        $queryData = [
            'botChatId' => $botChatId,
        ];
        error_log("SQL Query: $query, Data: " . json_encode($queryData));

        $bot = $this->DB->getRow($query, $queryData);
        error_log("Query Result: " . json_encode($bot));

        if (empty($bot)) {
            error_log("Bot not found for botChatId: $botChatId");
            return false;
        }

        $botId = $bot['id'];
        error_log("Bot ID: $botId");

        $result = $this->checkCondition('isBanned', $chatId, ['botId' => $botId]);
        error_log("checkCondition result: " . json_encode($result));

        $isBanned = !empty($result);
        error_log("isBanned result: " . ($isBanned ? 'true' : 'false'));

        return $isBanned;
    }

    public function isBannedBot(int|string $chatId): bool
    {
        $result = $this->checkCondition('isBannedBot', $chatId);

        return !empty($result);
    }

    public function isAdmin(int|string $chatId): bool
    {
        $user = $this->checkCondition('isAdmin', $chatId);

        if (empty($user)) {
            return false;
        }

        $botId = $user['0']['botId'];

        $query = "SELECT * FROM Bot WHERE id = '$botId'";
        $bot = $this->DB->getTable($query);

        return !empty($bot);
    }
}
