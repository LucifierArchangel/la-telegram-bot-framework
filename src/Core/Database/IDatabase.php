<?php

namespace Lucifier\Framework\Core\Database;

use mysqli;
use mysqli_result;

interface IDatabase
{
    public static function getInstance(): IDatabase;

    public function openNew(string $server, string $user, string $password, string $name, ?string $port = null): void;

    public function getConnection(): mysqli;

    public function query(string $sql, array $substitutions = []): mysqli_result|array|bool;

    public function substitute(string $sql, array $substitutions): string;

    public function escape(mixed $value): string;

    public function getCell(string $sql, array $substitutions = []): mixed;

    public function getRow(string $sql, array $substitutions = []): array;

    public function getColumn(string $sql, bool $makeHash = false, array $substitutions = []): array;

    public function getTable(string $sql, bool|string $keyCol = false, array $substitutions = []): array;

    public function writeRow(string $tableName, array $data, bool|int|array $unique_key = false, string|bool $mode = false): int|bool;

    public function convertKeyValuePairForWriting(mixed $key, mixed $value): string;

    public function close(): void;
}
