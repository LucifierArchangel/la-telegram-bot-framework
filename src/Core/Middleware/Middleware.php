<?php

namespace Lucifier\Framework\Core\Middleware;

use Lucifier\Framework\Core\Database\DB;
use Dotenv\Dotenv;

class Middleware
{
    /**
     * @var DB Database instance
     */
    protected DB $DB;

    /**
     * @var array Middleware configuration
     */
    protected array $config;

    /**
     * Path to the environment file
     */
    private const ENV_PATH = __DIR__ . '/../../../../../../';

    /**
     * Path to the middleware configuration file
     */
    private const CONFIG_PATH = __DIR__ . '/../../../config/middlewareConfig.php';

    public function __construct()
    {
        $this->initDb();
        $this->loadConfig();
    }

    /**
     * Initialize the database connection
     *
     * @return void
     */
    private function initDb(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(self::ENV_PATH);
        $dotenv->load();
        $this->DB = DB::getInstance();
        $this->DB->openNew('db', $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);
    }

    /**
     * Load the middleware configuration
     *
     * @return void
     */
    private function loadConfig(): void
    {
        $this->config = require self::CONFIG_PATH;
    }

    /**
     * Check a condition based on type and chatId
     *
     * @param string $type Condition type
     * @param int|string $chatId Chat ID
     * @return bool
     * @throws \Exception if the middleware type is unknown
     */
    private function checkCondition(string $type, int|string $chatId): bool
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

        return !empty($this->DB->getTable($query));
    }

    /**
     * Check if the user is banned
     *
     * @param int|string $chatId Chat ID
     * @return bool
     */
    public function isBanned(int|string $chatId): bool
    {
        return $this->checkCondition('isBanned', $chatId);
    }

    /**
     * Check if the user is an admin
     *
     * @param int|string $chatId Chat ID
     * @return bool
     */
    public function isAdmin(int|string $chatId): bool
    {
        return $this->checkCondition('isAdmin', $chatId);
    }
}
