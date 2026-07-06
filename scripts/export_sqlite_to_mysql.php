<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$sqlitePath = $argv[1] ?? $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite';
$outputPath = $argv[2] ?? $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . 'ead_epi_' . date('Ymd_His') . '.sql';

if (! is_file($sqlitePath)) {
    fwrite(STDERR, "SQLite database not found: {$sqlitePath}" . PHP_EOL);
    exit(1);
}

$outputDir = dirname($outputPath);
if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Could not create export directory: {$outputDir}" . PHP_EOL);
    exit(1);
}

$pdo = new PDO('sqlite:' . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$out = fopen($outputPath, 'wb');
if ($out === false) {
    fwrite(STDERR, "Could not open output file: {$outputPath}" . PHP_EOL);
    exit(1);
}

function writeLine($out, string $line = ''): void
{
    fwrite($out, $line . PHP_EOL);
}

function quoteName(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function quoteValue(mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . str_replace(["\\", "'", "\0"], ["\\\\", "\\'", ''], (string) $value) . "'";
}

function mysqlType(string $sqliteType): string
{
    $type = strtoupper(trim($sqliteType));

    return match (true) {
        str_contains($type, 'BIGINT') => 'BIGINT',
        str_contains($type, 'INT') => 'INT',
        str_contains($type, 'CHAR'), str_contains($type, 'CLOB'), str_contains($type, 'VARCHAR') => 'VARCHAR(255)',
        str_contains($type, 'TEXT') => 'TEXT',
        str_contains($type, 'BLOB') => 'LONGBLOB',
        str_contains($type, 'REAL'), str_contains($type, 'FLOA'), str_contains($type, 'DOUB') => 'DOUBLE',
        str_contains($type, 'DECIMAL'), str_contains($type, 'NUMERIC') => 'DECIMAL(15,2)',
        str_contains($type, 'BOOL') => 'TINYINT(1)',
        str_contains($type, 'DATE'), str_contains($type, 'TIME') => 'DATETIME',
        default => 'TEXT',
    };
}

$tables = $pdo->query("
    SELECT name
    FROM sqlite_master
    WHERE type = 'table'
      AND name NOT LIKE 'sqlite_%'
    ORDER BY name
")->fetchAll(PDO::FETCH_COLUMN);

writeLine($out, '-- Export generated from SQLite for MySQL/MariaDB import');
writeLine($out, '-- Source: ' . $sqlitePath);
writeLine($out, '-- Generated at: ' . date('Y-m-d H:i:s'));
writeLine($out);
writeLine($out, 'SET FOREIGN_KEY_CHECKS=0;');
writeLine($out, 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');
writeLine($out, 'SET NAMES utf8mb4;');
writeLine($out);

$summary = [];

foreach ($tables as $table) {
    $columns = $pdo->query('PRAGMA table_info(' . quoteName($table) . ')')->fetchAll();
    $indexes = $pdo->query('PRAGMA index_list(' . quoteName($table) . ')')->fetchAll();
    $primaryColumns = array_values(array_map(
        static fn (array $column): string => $column['name'],
        array_filter($columns, static fn (array $column): bool => (int) $column['pk'] > 0)
    ));

    writeLine($out, 'DROP TABLE IF EXISTS ' . quoteName($table) . ';');
    writeLine($out, 'CREATE TABLE ' . quoteName($table) . ' (');

    $definitions = [];
    foreach ($columns as $column) {
        $isSingleIntegerPrimaryKey = count($primaryColumns) === 1
            && $primaryColumns[0] === $column['name']
            && str_contains(strtoupper((string) $column['type']), 'INT');

        $definition = '  ' . quoteName($column['name']) . ' ' . ($isSingleIntegerPrimaryKey ? 'BIGINT UNSIGNED' : mysqlType((string) $column['type']));

        if ((int) $column['notnull'] === 1 || $isSingleIntegerPrimaryKey) {
            $definition .= ' NOT NULL';
        }

        if ($isSingleIntegerPrimaryKey) {
            $definition .= ' AUTO_INCREMENT';
        }

        if ($column['dflt_value'] !== null) {
            $default = trim((string) $column['dflt_value']);
            $definition .= ' DEFAULT ' . ($default === "''" ? "''" : $default);
        }

        $definitions[] = $definition;
    }

    if ($primaryColumns !== []) {
        $definitions[] = '  PRIMARY KEY (' . implode(', ', array_map('quoteName', $primaryColumns)) . ')';
    }

    foreach ($indexes as $index) {
        if ((int) $index['origin'] === 1 || str_starts_with((string) $index['name'], 'sqlite_autoindex_')) {
            continue;
        }

        $indexColumns = $pdo->query('PRAGMA index_info(' . quoteName((string) $index['name']) . ')')->fetchAll();
        $columnNames = array_map(static fn (array $item): string => quoteName($item['name']), $indexColumns);
        if ($columnNames === []) {
            continue;
        }

        $keyType = (int) $index['unique'] === 1 ? 'UNIQUE KEY' : 'KEY';
        $definitions[] = '  ' . $keyType . ' ' . quoteName((string) $index['name']) . ' (' . implode(', ', $columnNames) . ')';
    }

    writeLine($out, implode(',' . PHP_EOL, $definitions));
    writeLine($out, ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    writeLine($out);

    $rows = $pdo->query('SELECT * FROM ' . quoteName($table))->fetchAll();
    $summary[$table] = count($rows);

    if ($rows !== []) {
        $columnNames = array_keys($rows[0]);
        $insertPrefix = 'INSERT INTO ' . quoteName($table) . ' (' . implode(', ', array_map('quoteName', $columnNames)) . ') VALUES ';
        $batch = [];

        foreach ($rows as $row) {
            $batch[] = '(' . implode(', ', array_map('quoteValue', array_values($row))) . ')';

            if (count($batch) === 100) {
                writeLine($out, $insertPrefix . PHP_EOL . implode(',' . PHP_EOL, $batch) . ';');
                $batch = [];
            }
        }

        if ($batch !== []) {
            writeLine($out, $insertPrefix . PHP_EOL . implode(',' . PHP_EOL, $batch) . ';');
        }

        writeLine($out);
    }
}

writeLine($out, 'SET FOREIGN_KEY_CHECKS=1;');
fclose($out);

echo "Export created: {$outputPath}" . PHP_EOL;
foreach ($summary as $table => $count) {
    echo str_pad($table, 35) . $count . PHP_EOL;
}
