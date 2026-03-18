<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RefactorPrestamoFechaDevolucion extends AbstractMigration
{
    /**
     * Separa la fecha estimada de devolución (fecha_vencimiento) de la fecha
     * real de devolución (fecha_devolucion), que ahora es nullable y se
     * completa únicamente cuando el lector devuelve el ejemplar.
     */
    public function up(): void
    {
        // 1. Renombrar fecha_devolucion → fecha_vencimiento (NOT NULL, es la fecha estimada)
        $this->execute("
            ALTER TABLE prestamo
                CHANGE COLUMN fecha_devolucion
                    fecha_vencimiento DATETIME NOT NULL;
        ");

        // 2. Agregar fecha_devolucion como la fecha real de devolución (nullable)
        $this->execute("
            ALTER TABLE prestamo
                ADD COLUMN fecha_devolucion DATETIME NULL DEFAULT NULL
                AFTER fecha_vencimiento;
        ");

        // 3. Actualizar el índice para reflejar el nuevo nombre
        $this->execute("DROP INDEX idx_prestamo_fecha_devolucion ON prestamo;");
        $this->execute("CREATE INDEX idx_prestamo_fecha_vencimiento ON prestamo (fecha_vencimiento);");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX idx_prestamo_fecha_vencimiento ON prestamo;");
        $this->execute("CREATE INDEX idx_prestamo_fecha_devolucion ON prestamo (fecha_devolucion);");

        $this->execute("ALTER TABLE prestamo DROP COLUMN fecha_devolucion;");

        $this->execute("
            ALTER TABLE prestamo
                CHANGE COLUMN fecha_vencimiento
                    fecha_devolucion DATETIME NOT NULL;
        ");
    }
}
