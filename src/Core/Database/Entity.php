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

        public function __construct() {}

        public function setDB($db): void {
            $this->db = $db;
        }

        public function save(): void {
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