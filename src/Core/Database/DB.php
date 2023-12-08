<?php

    namespace Lucifier\Framework\Core\Database;

    use mysqli;

    class DB {
        var bool|mysqli $connection;
        public string $db_name;

        public function openNew(string $server, string $user, string $password, string $name) {
            $this->connection = mysqli_connect($server, $user, $password, $name);
            $this->db_name = $name;

            if (!$this->connection){
                die(mysqli_connect_error());
            }

            mysqli_query($this->connection,"SET NAMES utf8mb4");

            return $this->connection;
        }

        public function select(string $q, int $debug = 0): array {
            if ($q != "") {
                $list = array();

                $result = mysqli_query($this->connection,$q);
                while ($r = mysqli_fetch_assoc($result)) {
                    $list[] = $r;
                }

                $dbg = mysqli_errno($this->connection).":".mysqli_error($this->connection);

                if (mysqli_errno($this->connection) != "0") {
                    $echo = '[' . date('Y-m-d H:i:s') . ']('.$this->db_name.') '.$q.' ------ ' .$dbg. PHP_EOL;
                    file_put_contents('dblog.txt', $echo, FILE_APPEND);
                }

                return $list;
            } else {
                return array();
            }
        }

        public function insert(string $q, int $debug = 0): int|string {
            if ($q != "") {
                if(mysqli_query($this->connection,$q)) {
                    return mysqli_insert_id($this->connection);
                }else{
                    $dbg = mysqli_errno($this->connection).":".mysqli_error($this->connection);

                    if (mysqli_errno($this->connection) != "0") {
                        $echo = '[' . date('Y-m-d H:i:s') . ']('.$this->db_name.') '.$q.' ------ ' .$dbg. PHP_EOL;
                        file_put_contents('dblog.txt', $echo, FILE_APPEND);
                    }
                    return 0;
                }
            }

            return 0;
        }

        public function update(string $q, int $debug = 0) {
            if ($q != "") {
                if ($res = mysqli_query($this->connection,$q)) {
                    if (mysqli_affected_rows($this->connection) == 0) {
                        return 1;
                    } else {
                        return mysqli_affected_rows($this->connection);
                    }
                }

                $dbg = mysqli_errno($this->connection).":".mysqli_error($this->connection);
                if ($debug == 1) return mysqli_errno($this->connection).":".mysqli_error($this->connection)." | ".$q." |";
                if (mysqli_errno($this->connection) != "0") {
                    $echo = '[' . date('Y-m-d H:i:s') . ']('.$this->db_name.') '.$q.' ------ ' .$dbg. PHP_EOL;
                    file_put_contents('dblog.txt', $echo, FILE_APPEND);
                }
                return 0;
            }
        }

        public function delete(string $q = "", int $debug = 0){
            if ($q != "") {
                if($debug) {
                    echo $q;
                }
                return intval(mysqli_query($this->connection,$q)) * mysqli_affected_rows($this->connection);
            }
        }

        public function query(string $q = "", int $debug = 0){
            if ($q != "") {
                mysqli_query($this->connection,$q);

                if($debug) {
                    return mysqli_errno($this->connection) . ":" . mysqli_error($this->connection);
                }
            }
        }

        public function count(string $q = "", int $debug = 0) {
            if ($q != "") {
                if($debug) echo $q;
                $result = mysqli_query($this->connection,$q);
                $r = mysqli_fetch_assoc($result);
                if (isset($r['COUNT(1)'])) {
                    return $r['COUNT(1)'];
                }
                $dbg = mysqli_errno($this->connection).":".mysqli_error($this->connection);
                if ($debug) return $dbg;
                if (mysqli_errno($this->connection) != "0") {
                    $echo = '[' . date('Y-m-d H:i:s') . ']('.$this->db_name.') '.$q.' ------ ' .$dbg. PHP_EOL;
                    file_put_contents('dblog.txt', $echo, FILE_APPEND);
                }
                return $r['COUNT(0)'];
            }

            return 0;
        }

        public function close(mysqli $connect): void {
            mysqli_close($connect);
        }
    }
?>