<?php

namespace SmartPRO\Technology;

use PDO;
use PDOException;

/**
 * @Connect
 * @package SmartPRO\Technology
 */
class Connect
{
    /** @var PDO */
    protected static PDO $instance;

    /**
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (empty(self::$instance)) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . MYSQL['host'] . ";dbname=" . MYSQL['db'],
                    MYSQL['username'],
                    MYSQL['password'],
                    [
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                        PDO::ATTR_CASE => PDO::CASE_NATURAL
                    ]
                );
            } catch (PDOException $exception) {
                die("<h1>Sem conexão com o banco de dados</h1>");
            }
        }
        return self::$instance;
    }
}