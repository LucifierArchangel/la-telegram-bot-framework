<?php

    namespace Lucifier\Framework\Core\Database;

    define('DB_USER', '');
    define('DB_PASSWORD', '');
    define('DB_HOST', '');
    define('DB_PORT', '');
    define('DB_NAME', '');

    use Exception;
    use PDO;
    use ReflectionClass;
    use ReflectionProperty;

    abstract class Entity {
        protected $db;
        protected $tableName;

        public function __construct($db) {}

        public function setDB($db) {
            $this->db = $db;
        }

        public function open() {
            $this->openNew(DB_USER, DB_PASSWORD, DB_HOST, DB_PASSWORD, DB_NAME);
        }

        public function openNew(string $dbUser, string $dbPassword, string $dbHost, string $dbPort, string $dbName) {
            try {
                $this->db = new PDO('mysql:host='.$dbHost.':'.$dbPort.'dbname='.$dbName, $dbUser, $dbPassword);
            } catch (Exception $e) {
                throw new Exception("Error creating a database connection");
            }
        }

        public function save() {
            $class = new ReflectionClass($this);
            $tableName = '';

            if ($this->tableName !== '') {
                $tableName = $this->tableName;
            } else {
                $tableName = strtolower($class->getShortName());
            }

            $propsToImplode = [];

            foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $propertyName = $property->getName();
                $propsToImplode[] = '`'.$propertyName.'` = "'.$this->{$propertyName}.'"';
            }

            $setClause = implode(",", $propsToImplode);
            $sqlQuery = "";

            if (isset($this->id) && $this->id > 0) {
                $sqlQuery = 'UPDATE `'.$tableName.'` SET '.$setClause.' WHERE id='.$this->id;
            } else {
                $sqlQuery = 'INSERT INTO `'.$tableName.'` SET '.$setClause;
            }
        }
    }

?>