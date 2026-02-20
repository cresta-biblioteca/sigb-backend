<?php

namespace Tests;

use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar base de datos de prueba
        $this->pdo = $this->createTestDatabase();

        // Iniciar transacción para cada test
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Hacer rollback para limpiar datos del test
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    /**
     * Valida y escapa un identificador SQL (tabla o columna)
     * 
     * @param string $identifier Nombre de la tabla o columna
     * @return string Identificador escapado con backticks
     * @throws \InvalidArgumentException Si el identificador contiene caracteres no permitidos
     */
    private function escapeIdentifier(string $identifier): string
    {
        // Validar que solo contenga caracteres alfanuméricos y guiones bajos
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException(
                "Invalid identifier: '$identifier'. Only alphanumeric characters and underscores are allowed."
            );
        }

        // Escapar con backticks para MySQL
        return "`$identifier`";
    }

    /**
     * Crea la conexión a la base de datos de prueba
     */
    private function createTestDatabase(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'db';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = 'sigb_db_test'; // Siempre usa BD de testing
        $username = $_ENV['DB_USERNAME'] ?? 'sigb_user';
        $password = $_ENV['DB_PASSWORD'] ?? 'secret';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Helper genérico para insertar datos en cualquier tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array<string, mixed> $data Datos a insertar
     * @return int ID del registro insertado
     */
    protected function insertInto(string $table, array $data): int
    {
        $columns = array_keys($data);
        $escapedColumns = array_map(fn($col) => $this->escapeIdentifier($col), $columns);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->escapeIdentifier($table),
            implode(', ', $escapedColumns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Helper genérico para contar registros en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array<string, mixed> $where Condiciones WHERE (opcional)
     * @return int Cantidad de registros
     */
    protected function countRecords(string $table, array $where = []): int
    {
        $escapedTable = $this->escapeIdentifier($table);

        if (empty($where)) {
            $sql = "SELECT COUNT(*) FROM $escapedTable";
            $stmt = $this->pdo->query($sql);
        } else {
            $conditions = array_map(
                fn($col) => $this->escapeIdentifier($col) . " = :$col",
                array_keys($where)
            );
            $sql = sprintf(
                'SELECT COUNT(*) FROM %s WHERE %s',
                $escapedTable,
                implode(' AND ', $conditions)
            );
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($where);
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Helper para obtener un registro por ID
     * 
     * @param string $table Nombre de la tabla
     * @param int $id ID del registro
     * @return array<string, mixed>|null
     */
    protected function findById(string $table, int $id): ?array
    {
        $escapedTable = $this->escapeIdentifier($table);
        $sql = "SELECT * FROM $escapedTable WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Helper para truncar una tabla específica
     * 
     * @param string $table Nombre de la tabla
     */
    protected function truncateTable(string $table): void
    {
        $escapedTable = $this->escapeIdentifier($table);
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $this->pdo->exec("TRUNCATE TABLE $escapedTable");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Helper para eliminar registros de una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array<string, mixed> $where Condiciones WHERE
     */
    protected function deleteFrom(string $table, array $where): void
    {
        $conditions = array_map(
            fn($col) => $this->escapeIdentifier($col) . " = :$col",
            array_keys($where)
        );
        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->escapeIdentifier($table),
            implode(' AND ', $conditions)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($where);
    }

    /**
     * Helper para verificar si un registro existe
     * 
     * @param string $table Nombre de la tabla
     * @param array<string, mixed> $where Condiciones WHERE
     * @return bool
     */
    protected function recordExists(string $table, array $where): bool
    {
        return $this->countRecords($table, $where) > 0;
    }
}
