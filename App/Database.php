<?php

namespace App;


use PDO;
use PDOException;

class Database
{
    public static function getConnection(): PDO
    {
        try {
            $dsn = 'mysql:host=' . Config::DB_HOST . ';dbname=' . Config::DB_NAME . ';charset=utf8';
            $conn = new PDO($dsn, Config::DB_USERNAME, Config::DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo $e->getMessage();
            die();
        }

        return $conn;
    }

}