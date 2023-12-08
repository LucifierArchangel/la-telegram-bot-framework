<?php

    namespace Lucifier\Framework\Core\Database;

    use _PHPStan_79aa371a9\Nette\Neon\Exception;
    use mysqli;
    use mysqli_result;

    class DB {
        private mysqli $connection;
        public string $dbName;

        /**
         * Open new database connection
         * @param string $server     database's host
         * @param string $user       database's user
         * @param string $password   database's password
         * @param string $name       database's name
         * @return void
         */
        public function openNew(string $server, string $user, string $password, string $name): void {
            $this->connection = mysqli_connect($server, $user, $password, $name);
            $this->dbName = $name;

            mysqli_query($this->connection,"SET NAMES utf8mb4");
        }

        /**
         * Get current database connection
         *
         * @return mysqli
         */
        public function getConnection(): mysqli {
            return $this->connection;
        }

        /**
         * Run query and handle errors
         *
         * @param string $sql              sql query for running
         * @param array $substitutions     params for query replacing
         * @throws Exception
         */
        public function query(string $sql, array $substitutions=[]): mysqli_result|bool {
            if ($substitutions) {
                $sql = $this->substitute($sql, $substitutions);
            }

            echo $sql."\n";

            $result = $this->connection->query($sql);

            if ($result) {
                return $result;
            } else {
                throw new Exception("Database Error!!!");
            }
        }

        /**
         * Replace subtitions into query
         *
         * @param string $sql             query for $substitutions replacing
         * @param array $substitutions    params for query replacing
         * @return array|string|null
         */
        public function substitute(string $sql, array $substitutions): array|string|null {
            $that = $this;

            return preg_replace_callback(
                '/:(\w+)/',
                function ($matches) use($that, $substitutions) {
                    $key = $matches[1];
                    $value = (isset($substitutions[$key])) ? $substitutions[$key] : NULL;

                    return $that->escape($value);
                },
                $sql
            );
        }


        /**
         * Escape value
         *
         * @param mixed $value value for escaping
         * @return array|int|string
         */
        public function escape(mixed $value): array|int|string {
            if (is_array($value)) {
                $escaped = implode(',', array_map(['$this', 'escape'], $value) );
            } else {
                if (is_object($value) && (
                    get_class($value) === "DateTime" || is_subclass_of($value, "DateTime"))
                ) {
                    $value = $value->format("Y-m-d H:i:s");
                }
            }

            if (is_string($value)) {
                $escaped = "'".mysqli_real_escape_string($this->connection, $value)."'";
            } elseif (is_numeric($value)) {
                $escaped = str_replace(",", ".", (string)$value);
            } elseif (is_null($value)) {
                $escaped = "NULL";
            } else {
                $escaped = intval($value);
            }

            return $escaped;
        }

        /**
         * Get one cell
         *
         * @param string $sql
         * @param array $substitutions
         * @return false|mixed
         */
        public function getCell(string $sql, array $substitutions=[]): mixed {
            $tmp = $this->getColumn($sql, false, $substitutions);

            return ($tmp) ? reset($tmp) : false;
        }

        /**
         * Get one row
         *
         * @param string $sql
         * @param array $substitutions
         * @return array|false|mixed
         */
        public function getRow(string $sql, array $substitutions=[]): mixed {
            $tmp = $this->getTable($sql, false, $substitutions);

            return ($tmp) ? reset($tmp) : [];
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
        public function getColumn(string $sql, bool $makeHash=false, array $substitutions=[]): array {
            $data = [];

            $result = $this->query($sql, $substitutions);

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
         * For table queries
         *
         * @param string $sql
         * @param bool|string $keycol
         * @param array $substitutions
         * @return array
         * @throws Exception
         */
        public function getTable(string $sql, bool|string $keycol=false, array $substitutions=[]): array {
            $data = [];

            $result = $this->query($sql, $substitutions);

            if (!$keycol) {
                while ($row = mysqli_fetch_row($result)) {
                    $data[] = $row;
                }
            } else {
                while ($row = mysqli_fetch_row($result)) {
                    $data[$row[$keycol]] = $row;
                }
            }

            return $data;
        }

        /**
         * Write or update data
         *
         * @param $tableName
         * @param $data
         * @param $unique_key
         * @param $mode
         * @return int|string
         * @throws Exception
         */
        public function writeRow(string $tableName, array $data, mixed $unique_key = false, string|bool $mode=false): int|string {
            if (!$unique_key) {
                if (!$mode) {
                    $sql = "INSERT";
                } elseif ($mode === "IGNORE") {
                    $sql = "INSERT IGNORE";
                } else if ($mode === "REPLACE") {
                    $sql = "REPLACE";
                } else {
                    throw new Exception("Unknown mode \"$mode\"");
                }

                $sql .= " INTO $tableName ";

                if ($data) {
                    $sql .= " SET ";

                    foreach ($data as $key => $value) {
                        $sql .= $this->convertKeyValuePairForWriting($key, $value).', ';
                    }

                    $sql = substr($sql, 0, -2);
                } else {
                    $sql .= " VALUES () ";
                }

                $result = $this->query($sql);

                $out = $this->connection->insert_id;
            } else {
                if (!is_array($unique_key)) {
                    if (!$mode) {
                        $unique_key = array('id' => $unique_key);
                    } else {
                        $unique_key = array($unique_key);
                    }
                }

                foreach ($unique_key as $key=>$value) {
                    if (is_numeric($key) && !isset($data[$key])) {
                        $forMsg[] = $value;
                    }

                    if ($forMsg ?? false) {
                        throw new Exception("Query error");
                    }
                }
                unset($key, $value, $forMsg);

                if (!$mode) {
                    $sql = "UPDATE $tableName SET ";

                    foreach ($data as $key => $value) {
                        $sql .= $this->convertKeyValuePairForWriting($key, $value).', ';
                    }

                    $sql = substr($sql, 0, -2);

                    if ($unique_key) {
                        foreach ($unique_key as $key => $value) {
                            $part = " `$key` ";
                            $part .= (!is_null($value))
                                ? " = " . $this->escape($value)
                                : " IS NULL";
                            $parts[] = $part;
                        }

                        $sql .= " WHERE (".implode(") AND (", $parts).")";
                        unset($parts, $part);
                    }

                    $resutl = $this->query($sql);

                    $out = $this->connection->insert_id;
                } elseif ($mode === 'DUPLICATE') {
                    $append = is_string(key($unique_key));

                    if ($append) {
                        $allData = $data + $unique_key;
                        $dataToUpdate = $data;
                    } else {
                        $allData = $data;
                        $dataToUpdate = array_diff_key($data, array_fill_keys($unique_key, true));
                    }

                    $sql = "INSERT INTO $tableName SET ";

                    foreach ($allData as $key => $value) {
                        $sql .= $this->convertKeyValuePairForWriting($key, $value).', ';
                    }

                    $sql = substr($sql, 0, -2);

                    if ($dataToUpdate) {
                        $sql .= " ON DUPLICATE KEY UPDATE ";

                        foreach ($dataToUpdate as $key => $value) {
                            $sql .= $this->convertKeyValuePairForWriting($key, $value).', ';
                        }

                        $sql = substr($sql, 0, -2);
                    }

                    $result = $this->query($sql);

                    $out = $this->connection->insert_id;
                }
            }

            return $out;
        }

        /**
         * Convert key value pair to escape string for writing
         * @param mixed $key
         * @param mixed $value
         * @return string
         */
        public function convertKeyValuePairForWriting(mixed $key, mixed $value): string {
            $sql = '';

            if (strpos($key, '.') === false) {
                $sql .= "`$key` = ";

                if (is_array($value)) {
                    $v = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $v = $value;
                }

                $sql .= $this->escape($v);
            } else {
                $tmp = explode(".", $key);
                $column = $tmp[0];
                $rest = $tmp[1];
                $path = preg_replace_callback("/\.(\w+)/", function ($matches) {
                    return (is_numeric($matches[1]))
                        ? "[".$matches[1]."]"
                        : ".".$matches[1];
                }, $rest);
                $path = preg_replace("/[^\w\.\[\]]/", "", $path);

                $sql .= "`$column` = JSON_SET(IFNULL(`$column`, '{}'), '\$$path', ";
                $sql .= (!is_array($value))
                    ? $this->escape($value)
                    : "JSON_EXTRACT(".$this->escape(json_encode($value, JSON_UNESCAPED_UNICODE)). ", '$')";
                $sql .= ")";
            }

            return $sql;
        }

        public function close(mysqli $connect): void {
            mysqli_close($connect);
        }
    }
?>