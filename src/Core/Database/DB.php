<?php

namespace Lucifier\Framework\Core\Database;

use Exception;
use Lucifier\Framework\Utils\Logger\FileLogger;
use mysqli;
use mysqli_result;

class DB implements IDatabase
{
    private static $instance;
    private mysqli $connection;
    public string $dbName;

    private string $server;
    private string $user;
    private string $password;
    private string $database;
    private ?string $port;

    private ?int $lastPingCheck = null;
    private int $pingInterval = 360;

    /**
     * Constructor for the class.
     *
     * This constructor registers a shutdown function to ensure that the database connection is closed
     * when the script execution ends. The `close` method will be automatically called during shutdown
     * to release any active resources.
     *
     * @return void
     */
    public function __construct()
    {
        register_shutdown_function(function () {
            $this->close();
        });
    }

    /**
     * Get singleton for current database class realisation
     *
     * @return IDatabase
     */
    public static function getInstance(): IDatabase
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Open new database connection
     * @param string $server database's host
     * @param string $user database's user
     * @param string $password database's password
     * @param string $name database's name
     * @param ?string $port database's port
     * @return void
     */
    public function openNew(
        string  $server,
        string  $user,
        string  $password,
        string  $name,
        ?string $port = null
    ): void {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $name;
        $this->port = $port;

        $this->connection = mysqli_connect(
            $this->server,
            $this->user,
            $this->password,
            $this->database = $name,
            $this->port
        );

        $this->dbName = $name;

        mysqli_query($this->connection, "SET NAMES utf8mb4");
    }

    /**
     * Get current database connection
     *
     * @return mysqli
     */
    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    /**
     * Executes an SQL query on the database connection, with optional substitutions.
     *
     * This method allows for executing SQL queries and handles optional parameter substitution
     * to prepare the SQL query dynamically. If the query is successful, it returns the result
     * of the query. For `UPDATE` or `DELETE` statements, it will return `false` if no rows were
     * affected. Throws an exception if there is a database error.
     *
     * @param string $sql           The raw SQL query to execute.
     * @param array  $substitutions An optional associative array of substitutions to replace
     *                               placeholders in the query. Default is an empty array.
     *
     * @return mysqli_result|bool   Returns a `mysqli_result` object for successful SELECT queries.
     *                              Returns `true` for successful queries that do not produce a result set.
     *                              Returns `false` if an `UPDATE` or `DELETE` query affected no rows.
     *
     * @throws Exception            If a database error occurs during the query execution.
     */
    public function query(string $sql, array $substitutions = []): mysqli_result|array|bool
    {
        $this->ensureConnection();

        if (!empty($substitutions)) {
            $sql = $this->substitute($sql, $substitutions);
        }

        $result = $this->connection->query($sql);

        if ($result === false) {
            throw new QueryException("Database error: {$this->connection->error} (Error Code: {$this->connection->errno})");
        }

        if (
            preg_match('/^\s*(UPDATE|DELETE)/i', $sql)
            && $this->connection->affected_rows === 0
        ) {
            return false;
        }

        if (
            preg_match('/^\s*SELECT/i', $sql)
            && $result instanceof mysqli_result
            && $result->num_rows === 0
        ) {
            return [];
        }

        if (
            $result instanceof mysqli_result
            && $result->num_rows > 1
        ) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            return $rows;
        }

        return $result;
    }

    /**
     * Ensures that the database connection is active.
     *
     * This method checks if the database connection exists and is responsive using the `ping` method.
     * If the connection is not active, it re-establishes a new connection by calling the `openNew` method
     * with the stored connection details (`server`, `user`, `password`, `database`, and `port`).
     *
     * @return void
     */
    public function ensureConnection(): void
    {
        $currentTime = time();

        if (
            !isset($this->connection)
            || ($this->lastPingCheck === null || $currentTime - $this->lastPingCheck > $this->pingInterval)
            || !$this->connection->ping()
        ) {
            $this->openNew($this->server, $this->user, $this->password, $this->database, $this->port);
            $this->lastPingCheck = $currentTime;
        }
    }

    /**
     * Replaces placeholders in an SQL query with their corresponding values from substitutions.
     *
     * This method scans the given SQL query for named placeholders (e.g., `:placeholder`) and replaces
     * them with properly escaped values from the `$substitutions` array. The `escape` method is used
     * to ensure that the values are safe for use in the query.
     *
     * @param string $sql The SQL query containing named placeholders (e.g., `:placeholder`).
     * @param array $substitutions An associative array where keys correspond to placeholder names
     *                               (without the colon) and values are the values to replace them with.
     *
     * @return string               The SQL query with placeholders replaced by their corresponding values.
     *
     * @throws \RuntimeException    If an error occurs during the substitution process
     *                              (e.g., `preg_replace_callback` returns null).
     */
    public function substitute(string $sql, array $substitutions): string
    {
        $result = preg_replace_callback(
            '/:(\w+)/',
            function ($matches) use ($substitutions) {
                $key = $matches[1];
                $value = $substitutions[$key] ?? NULL;

                return $this->escape($value);
            },
            $sql
        );

        if (is_null($result)) {
            throw new \RuntimeException('Error during substitution with preg_replace_callback.');
        }

        return $result;
    }

    /**
     * Escapes a value for safe inclusion in an SQL query.
     *
     * This method ensures that the given value is properly escaped and formatted for use in an SQL query,
     * preventing SQL injection vulnerabilities. It supports multiple data types, including strings, numbers,
     * arrays, `DateTime` objects, and `NULL`. If an unsupported data type is provided, an exception is thrown.
     *
     * - Strings are escaped using `mysqli_real_escape_string`.
     * - Arrays are recursively escaped, and their values are joined with commas.
     * - `DateTime` objects are formatted as strings in the `'Y-m-d H:i:s'` format.
     * - Numeric values are converted to strings and use a dot as the decimal separator.
     * - `NULL` is returned as the string `'NULL'`.
     *
     * @param mixed $value          The value to be escaped. Can be a string, number, array, `DateTime` object, or `NULL`.
     *
     * @return string               The escaped value, ready for inclusion in an SQL query.
     *
     * @throws \InvalidArgumentException If the provided value is of an unsupported data type.
     */
    public function escape(mixed $value): string
    {
        if (is_array($value)) {
            $escapedArray = array_map([$this, 'escape'], $value);
            return implode(',', $escapedArray);
        }

        if ($value instanceof \DateTime) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }

        if (is_string($value)) {
            return "'" . mysqli_real_escape_string($this->connection, $value) . "'";
        }

        if (is_numeric($value)) {
            return str_replace(',', '.', (string)$value);
        }

        if (is_null($value)) {
            return 'NULL';
        }

        throw new \InvalidArgumentException('Unsupported data type for SQL escaping.');
    }

    /**
     * Get one cell
     *
     * @param string $sql
     * @param array $substitutions
     * @return false|mixed
     * @throws Exception
     */
    public function getCell(string $sql, array $substitutions = []): mixed
    {
        $tmp = $this->getColumn($sql, false, $substitutions);

        return ($tmp) ? reset($tmp) : false;
    }

    /**
     * Executes an SQL query and retrieves a single row as an associative array.
     *
     * This method runs the provided SQL query, optionally substituting placeholders with given values,
     * and fetches the first row of the result set. If no rows are returned, an empty array is returned.
     *
     * @param string $sql           The SQL query to execute.
     * @param array  $substitutions An optional associative array of substitutions to replace
     *                               placeholders in the query. Default is an empty array.
     *
     * @return array                An associative array representing the first row of the result set.
     *                              Returns an empty array if no rows are found.
     *
     * @throws \RuntimeException    If the query fails to execute or if there is a database error.
     */
    public function getRow(string $sql, array $substitutions = []): array
    {
        $this->ensureConnection();

        if (!empty($substitutions)) {
            $sql = $this->substitute($sql, $substitutions);
        }

        $result = $this->connection->query($sql);

        if ($result === false) {
            throw new QueryException("Database error: {$this->connection->error} (Error Code: {$this->connection->errno})");
        }

        if (!$result instanceof mysqli_result) {
            return [];
        }

        $row = $result->fetch_assoc();

        return $row ?: [];
    }

    /**
     * Get one column
     *
     * @param string $sql
     * @param bool $makeHash
     * @param array $substitutions
     * @return array
     * @throws Exception
     */
    public function getColumn(string $sql, bool $makeHash = false, array $substitutions = []): array
    {
        $data = [];

        $result = $this->query($sql, $substitutions, true);

        if (!$makeHash) {
            while ($row = mysqli_fetch_row($result)) {
                $data[] = $row[0];
            }
        } else {
            while ($row = mysqli_fetch_row($result)) {
                $data[$row[0]] = $row[1];
            }
        }

        return $data;
    }

    /**
     * Executes an SQL query and retrieves all rows as an associative array, optionally keyed by a specific column.
     *
     * This method runs the given SQL query, with optional parameter substitutions, and fetches all rows
     * from the result set. By default, the rows are returned as a numeric-indexed array of associative arrays.
     * If a specific key column is provided, the rows are re-indexed using the values from that column.
     *
     * @param string       $sql           The SQL query to execute.
     * @param bool|string  $keyCol        The name of the column to use as the array key for the rows.
     *                                    If `false`, the result is returned as a numeric-indexed array. Default is `false`.
     * @param array        $substitutions An optional associative array of substitutions to replace
     *                                    placeholders in the query. Default is an empty array.
     *
     * @return array                      An array of rows, either as a numeric-indexed array (if `$keyCol` is `false`)
     *                                    or as an associative array keyed by the specified column.
     *
     * @throws \RuntimeException          If the query fails to execute or there is a database error.
     * @throws \InvalidArgumentException  If the specified key column does not exist in the result set.
     */
    public function getTable(string $sql, bool|string $keyCol = false, array $substitutions = []): array
    {
        $this->ensureConnection();

        if (!empty($substitutions)) {
            $sql = $this->substitute($sql, $substitutions);
        }

        $result = $this->connection->query($sql);

        if ($result === false) {
            throw new QueryException("Database error: {$this->connection->error} (Error Code: {$this->connection->errno})");
        }

        if (!$result instanceof mysqli_result) {
            return [];
        }

        if (!$result) {
            throw new \RuntimeException('Failed to execute query: ' . $this->connection->error);
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);

        if ($keyCol === false) {
            return $rows;
        }

        if (
            empty($rows)
            || !array_key_exists($keyCol, $rows[0])
        ) {
            throw new \InvalidArgumentException("Key column '{$keyCol}' does not exist in the result set.");
        }

        $data = [];
        foreach ($rows as $row) {
            $data[$row[$keyCol]] = $row;
        }

        return $data;
    }

    /**
     * Write or update a row in the database table.
     *
     * This method inserts or updates a row in the specified table, depending on the provided parameters.
     * It supports various modes for inserting data and conditions for updating existing rows.
     *
     * @param string $tableName
     *   The name of the database table.
     *
     * @param array $data
     *   An associative array of data to insert or update, where keys are column names and values are their respective values.
     *
     * @param int|bool $unique_key
     *   A unique identifier or an array of conditions used to find an existing row for updating.
     *   - If `false`, a new row is inserted.
     *   - If an `int`, it is treated as the `id` of the row to update.
     *   - If an array, it is treated as a set of conditions for identifying the row.
     *
     * @param string|bool $mode
     *   The mode of operation for the write operation:
     *   - `false` (default): Insert or update a row.
     *   - `'IGNORE'`: Use `INSERT IGNORE` to avoid duplicate key errors.
     *   - `'REPLACE'`: Use `REPLACE` to insert a new row or replace an existing row.
     *   - `'DUPLICATE'`: Use `INSERT ... ON DUPLICATE KEY UPDATE` to update on duplicate keys.
     *   - Throws an exception if an unknown mode is provided.
     *
     * @return int|bool
     *   The result of the operation:
     *   - The `insert_id` of the inserted row (for insert operations).
     *   - The `id` of the updated row if successful, or `true` if a condition-based update succeeds.
     *   - `false` if no rows were affected.
     *
     * @throws Exception
     *   Throws an exception if an invalid mode is provided or if an error occurs during query execution.
     */
    public function writeRow(
        string         $tableName,
        array          $data,
        int|bool|array $unique_key = false,
        string|bool    $mode = false
    ): int|bool {
        $out = false;

        if (!$unique_key) {
            $sql = match ($mode) {
                false     => 'INSERT',
                'IGNORE'  => 'INSERT IGNORE',
                'REPLACE' => 'REPLACE',
                default   => throw new Exception("Unknown mode \"$mode\""),
            };

            $sql .= " INTO $tableName SET " . implode(", ", array_map(
                    fn($key, $value) => $this->convertKeyValuePairForWriting($key, $value),
                    array_keys($data), $data
                ));

            $this->query($sql);
            $out = $this->connection->insert_id;
        } else {
            if (!is_array($unique_key)) {
                $unique_key = ['id' => $unique_key];
            }

            $where = implode(" AND ", array_map(
                fn($key, $value) => "`$key` " . ($value !== null ? "= " . $this->escape($value) : "IS NULL"),
                array_keys($unique_key), $unique_key
            ));

            if (!$mode) {
                $sql = "UPDATE $tableName SET " . implode(", ", array_map(
                        fn($key, $value) => $this->convertKeyValuePairForWriting($key, $value),
                        array_keys($data), $data
                    )) . " WHERE $where";

                $this->query($sql);

                if ($this->connection->affected_rows > 0) {
                    $out = $unique_key['id'] ?? true;
                }
            } elseif ($mode === 'DUPLICATE') {
                $allData = $data + $unique_key;
                $sql = "INSERT INTO $tableName SET " . implode(", ", array_map(
                        fn($key, $value) => $this->convertKeyValuePairForWriting($key, $value),
                        array_keys($allData), $allData
                    ));

                if ($data) {
                    $sql .= " ON DUPLICATE KEY UPDATE " . implode(", ", array_map(
                            fn($key, $value) => $this->convertKeyValuePairForWriting($key, $value),
                            array_keys($data), $data
                        ));
                }

                $this->query($sql);

                $out = $this->connection->insert_id ?: ($this->connection->affected_rows > 0);
            }
        }
        return $out;
    }

    /**
     * Converts a key-value pair into a string suitable for use in an SQL query.
     *
     * This method prepares a key-value pair for writing to an SQL query by escaping the value and formatting it
     * as a column assignment (`key = value`). If the key contains a dot (`.`), it assumes a JSON field reference
     * and delegates processing to the `convertJsonKeyValuePair` method. For array values, the method JSON-encodes
     * the array and escapes it.
     *
     * @param mixed $key   The key or column name. If it contains a dot (`.`), it is treated as a JSON field reference.
     * @param mixed $value The value to be assigned to the key. Can be a string, array, or any data type supported
     *                     by the `escape` method. Arrays are JSON-encoded before escaping.
     *
     * @return string      A string formatted as `key = value`, ready for use in an SQL query.
     *
     * @throws \RuntimeException       If the JSON encoding of the value fails.
     * @throws \InvalidArgumentException If the key is invalid or unsupported by the method.
     */
    public function convertKeyValuePairForWriting(mixed $key, mixed $value): string
    {
        if (strpos($key, '.') !== false) {
            return $this->convertJsonKeyValuePair($key, $value);
        }

        $escapedValue = is_array($value)
            ? $this->escape(json_encode($value, JSON_UNESCAPED_UNICODE))
            : $this->escape($value);

        return "`$key` = $escapedValue";
    }

    /**
     * Converts a JSON key-value pair into a string suitable for use in an SQL query.
     *
     * This method handles the assignment of values to JSON fields in an SQL column. If the key represents
     * a JSON path (e.g., `column.subfield`), it converts the path into a valid JSON path expression
     * and uses SQL functions like `JSON_SET` and `IFNULL` to ensure the JSON structure is updated correctly.
     * The value is properly escaped, and arrays are JSON-encoded before processing.
     *
     * @param string $key   The JSON key or column path in the format `column.subfield`.
     *                      The part before the dot (`.`) is the column name, and the part after the dot
     *                      represents the JSON path within the column.
     * @param mixed  $value The value to assign to the JSON field. Can be a scalar or an array. Arrays
     *                      are JSON-encoded and inserted into the JSON field using `JSON_EXTRACT`.
     *
     * @return string       A string formatted as a JSON column update (`column = JSON_SET(...)`), ready for use
     *                      in an SQL query.
     *
     * @throws \RuntimeException       If the JSON encoding of the value fails.
     * @throws \InvalidArgumentException If the key is invalid or unsupported by the method.
     */
    private function convertJsonKeyValuePair(string $key, mixed $value): string
    {
        $tmp = explode('.', $key);
        $column = $tmp[0];
        $rest = $tmp[1];

        $path = preg_replace_callback('/\.(\w+)/', function ($matches) {
            return is_numeric($matches[1]) ? "[{$matches[1]}]" : ".{$matches[1]}";
        }, $rest);

        $path = preg_replace("/[^\w\.\[\]]/", "", $path);

        $escapedValue = is_array($value)
            ? "JSON_EXTRACT(" . $this->escape(json_encode($value, JSON_UNESCAPED_UNICODE)) . ", '$')"
            : $this->escape($value);

        return "`$column` = JSON_SET(IFNULL(`$column`, '{}'), '\$$path', $escapedValue)";
    }

    /**
     * Closes the database connection.
     *
     * This method safely closes the active MySQL database connection if it exists and is an instance of `mysqli`.
     * After closing the connection, it unsets the `$connection` property to free up resources and prevent further use.
     *
     * @return void
     */
    public function close(): void
    {
        if (
            isset($this->connection)
            && $this->connection instanceof mysqli
        ) {
            $this->connection->close();
            unset($this->connection);
        }
    }
}
