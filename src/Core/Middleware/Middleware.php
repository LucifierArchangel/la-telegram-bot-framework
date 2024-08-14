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
        $dotenv = \Dotenv\Dotenv::createImmutable(self::ENV_PATH);
        $dotenv->load();
        $this->DB = DB::getInstance();
        $this->DB->openNew('db', $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);
    }

    private function loadConfig(): void
    {
        $this->config = require self::CONFIG_PATH;
    }

    private function checkCondition(string $type, int|string $chatId): bool|array
    {
        if (!isset($this->config[$type])) {
            throw new \Exception('Unknown middleware type:' . $type);
        }

        $config = $this->config[$type];
        $table = $config['table'];
        $conditions = $config['conditions'];

        $queryConditions = [];
        foreach ($conditions as $field => $value) {
            $value = ($value === ':chatId') ? $chatId : $value;
            $queryConditions[] = "$field = '$value'";
        }
        $queryConditionString = implode(' AND ', $queryConditions);

        $query = "SELECT * FROM $table WHERE $queryConditionString";

        return $this->DB->getTable($query);
    }

    public function isBanned(int|string $chatId): bool
    {
        $result = $this->checkCondition('isBanned', $chatId);

        return !empty($result);
    }

    public function isAdmin(int|string $chatId): bool
    {
        $user = $this->checkCondition('isAdmin', $chatId);

        if (empty($user)) {
            return false;
        }

        $botId = $user['0']['botId'];
        $ownerId = $user['0']['id'];

        $query = "SELECT * FROM Bot WHERE id = '$botId' AND ownerId = '$ownerId'";
        $bot = $this->DB->getTable($query);

        return !empty($bot);
    }
}
