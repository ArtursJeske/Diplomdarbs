<?php

require_once __DIR__ . '/../config.php';

function db(): PDO 
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE               =>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    =>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES      =>false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Neizdevās pieslēgties datubāzei, pārbaudiet config.php iestaījumus.');
        }
    }
    return $pdo;
}


function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch_all(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $row = query($sql, $params)->fetch();
    return $row === false ? null : $row;
}


function fetch_value(string $sql, array $params = [])
{
    return query($sql, $params)->fetchColumn();
}