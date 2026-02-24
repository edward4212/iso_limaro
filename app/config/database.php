<?php
// app/config/database.php

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $host = 'localhost';
        $db   = 'limarocloud_iso';
        $user = 'limarocloud_user';
        $pass = 'iXAlb2OUN0Lk';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->connection = new PDO($dsn, $user, $pass, $options);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
