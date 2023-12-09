<?php

    namespace Lucifier\Framework\Core\Database;

    use mysqli;

    interface IDatabase {
        public function openNew(string $server, string $user, string $password, string $name): void;

        public function getConnection(): mixed;

        public function query(string $sql, array $substitutions=[]): mixed;

        public function substitute(string $sql, array $substitutions): array|string|null;

        public function escape(mixed $value): array|int|string;

        public function getCell(string $sql, array $substitutions=[]): mixed;

        public function getRow(string $sql, array $substitutions=[]): mixed;

        public function getColumn(string $sql, bool $makeHash=false, array $substitutions=[]): array;

        public function getTable(string $sql, bool|string $keyCol=false, array $substitutions=[]): array;

        public function writeRow(string $tableName, array $data, mixed $unique_key=false, string|bool $mode=false): int|string;

        public function convertKeyValuePairForWriting(mixed $key, mixed $value): string;

        public function close(?mysqli $connect): void;
    }

?>