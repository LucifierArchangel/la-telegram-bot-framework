<?php

    namespace Lucifier\Framework\Core\Database;

    use ReflectionClass;

    define('DB_USER', '');
    define('DB_PASSWORD', '');
    define('DB_HOST', '');
    define('DB_PORT', '');
    define('DB_NAME', '');

    abstract class Entity {
        protected IDatabase $db;
        protected $tableName;

        public function __construct() {}

        public function setDB($db): void {
            $this->db = $db;
        }

        public function createOrStatement($or) {
            $propsToImplode = [];

            foreach ($or as $item) {
                $propsToImplode[] = $this->createWhereCondition($item);
            }

            return " ( ".implode(" OR ", $propsToImplode)." )";
        }

        private function createWhereCondition($where) {
            $propsToImplode = [];

            foreach ($where as $key => $value) {
                if (!is_array($value)) {
                    $propsToImplode[] = $this->db->convertKeyValuePairForWriting($key, $value);
                } elseif (is_array($value)) {
                    if ($key === 'OR') {
                        $propsToImplode[] = $this->createOrStatement($value);
                    } elseif ($key === 'AND') {
                        $propsToImplode[] = $this->createWhereCondition($value);
                    } else {

                    }
                }
            }

            return implode(' AND ', $propsToImplode);
        }

        public function getFindSql(array $conditions) {
            $class = new ReflectionClass($this);

            if (isset($this->tableName)) {
                $tableName = $this->tableName;
            } else {
                $tableName = $class->getShortName();
            }

            if (isset($conditions["where"])) {
                $whereClause = $this->createWhereCondition($conditions["where"]);
            }

            $sql = "SELECT * FROM ".$tableName;
            if (isset($whereClause)) {
                $sql .= " WHERE". $whereClause;
            }

            return $sql;
        }

        public function findMany(array $conditions = []): array {
            $sql = $this->getFindSql($conditions);

            return $this->db->getTable($sql);
        }

        public function findFirst(array $conditions = []): array {
            $sql = $this->getFindSql($conditions)." LIMIT 1";

            return $this->db->getTable($sql);
        }
    }

?>