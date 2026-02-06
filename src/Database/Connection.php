<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Clase para manejar la conexión a la base de datos
 */
class Connection
{
    private static ?PDO $instance = null;

    /**
     * Obtiene una instancia de conexión a la base de datos (Singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }

        return self::$instance;
    }

    /**
     * Establece la conexión con la base de datos
     */
    private static function connect(): void
    {
        $host = $_ENV['DB_HOST'] ?? 'db';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? 'sigb_db';
        $username = $_ENV['DB_USERNAME'] ?? 'sigb_user';
        $password = $_ENV['DB_PASSWORD'] ?? 'sigb_password';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Previene la clonación de la instancia
     */
    private function __clone()
    {
    }

    /**
     * Previene la deserialización de la instancia
     */
    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar un singleton.");
    }
}
