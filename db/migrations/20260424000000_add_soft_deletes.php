<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSoftDeletes extends AbstractMigration
{
    public function up(): void
    {
        // tipo_prestamo: reemplazar habilitado por deleted_at
        $this->execute("ALTER TABLE tipo_prestamo DROP COLUMN habilitado;");
        $this->execute("ALTER TABLE tipo_prestamo ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // ejemplar: reemplazar habilitado por deleted_at
        $this->execute("ALTER TABLE ejemplar DROP COLUMN habilitado;");
        $this->execute("ALTER TABLE ejemplar ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // user
        $this->execute("ALTER TABLE `user` ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // lector
        $this->execute("ALTER TABLE lector ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // articulo
        $this->execute("ALTER TABLE articulo ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // carrera
        $this->execute("ALTER TABLE carrera ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // persona
        $this->execute("ALTER TABLE persona ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;");

        // índices para filtros WHERE deleted_at IS NULL
        $this->execute("CREATE INDEX idx_user_deleted_at ON `user` (deleted_at);");
        $this->execute("CREATE INDEX idx_lector_deleted_at ON lector (deleted_at);");
        $this->execute("CREATE INDEX idx_articulo_deleted_at ON articulo (deleted_at);");
        $this->execute("CREATE INDEX idx_ejemplar_deleted_at ON ejemplar (deleted_at);");
        $this->execute("CREATE INDEX idx_tipo_prestamo_deleted_at ON tipo_prestamo (deleted_at);");
        $this->execute("CREATE INDEX idx_carrera_deleted_at ON carrera (deleted_at);");
        $this->execute("CREATE INDEX idx_persona_deleted_at ON persona (deleted_at);");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX idx_persona_deleted_at ON persona;");
        $this->execute("DROP INDEX idx_carrera_deleted_at ON carrera;");
        $this->execute("DROP INDEX idx_tipo_prestamo_deleted_at ON tipo_prestamo;");
        $this->execute("DROP INDEX idx_ejemplar_deleted_at ON ejemplar;");
        $this->execute("DROP INDEX idx_articulo_deleted_at ON articulo;");
        $this->execute("DROP INDEX idx_lector_deleted_at ON lector;");
        $this->execute("DROP INDEX idx_user_deleted_at ON `user`;");

        $this->execute("ALTER TABLE persona DROP COLUMN deleted_at;");
        $this->execute("ALTER TABLE carrera DROP COLUMN deleted_at;");
        $this->execute("ALTER TABLE articulo DROP COLUMN deleted_at;");
        $this->execute("ALTER TABLE lector DROP COLUMN deleted_at;");
        $this->execute("ALTER TABLE `user` DROP COLUMN deleted_at;");

        $this->execute("ALTER TABLE ejemplar DROP COLUMN deleted_at;");
        $this->execute("ALTER TABLE ejemplar ADD COLUMN habilitado BOOL NOT NULL DEFAULT 1;");

        $this->execute("ALTER TABLE tipo_prestamo DROP COLUMN deleted_at;");
        $this->execute("ALTER TABLE tipo_prestamo ADD COLUMN habilitado BOOL NOT NULL DEFAULT 1;");
    }
}
