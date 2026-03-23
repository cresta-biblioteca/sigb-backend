<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPersonaAndMarc21Fields extends AbstractMigration
{
    public function up(): void
    {
        // 1. Crear tabla persona
        $this->execute("
            CREATE TABLE persona (
                id BIGINT NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                apellido VARCHAR(100) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX uq_persona_nombre_apellido (nombre, apellido),
                CONSTRAINT persona_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Crear tabla libro_persona (pivote)
        $this->execute("
            CREATE TABLE libro_persona (
                libro_id BIGINT NOT NULL,
                persona_id BIGINT NOT NULL,
                rol ENUM('autor','coautor','colaborador','editor','traductor','ilustrador') NOT NULL,
                orden INT NOT NULL DEFAULT 0,
                CONSTRAINT libro_persona_pk PRIMARY KEY (libro_id, persona_id, rol),
                INDEX idx_libro_persona_persona_id (persona_id),
                CONSTRAINT fk_libro_persona_libro
                    FOREIGN KEY (libro_id) REFERENCES libro(articulo_id) ON DELETE CASCADE,
                CONSTRAINT fk_libro_persona_persona
                    FOREIGN KEY (persona_id) REFERENCES persona(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 3. Agregar columnas nuevas a libro
        $this->execute("
            ALTER TABLE libro
                ADD COLUMN edicion VARCHAR(100) NULL AFTER lugar_de_publicacion,
                ADD COLUMN dimensiones VARCHAR(50) NULL AFTER edicion,
                ADD COLUMN ilustraciones VARCHAR(100) NULL AFTER dimensiones,
                ADD COLUMN serie VARCHAR(255) NULL AFTER ilustraciones,
                ADD COLUMN numero_serie VARCHAR(50) NULL AFTER serie,
                ADD COLUMN notas TEXT NULL AFTER numero_serie,
                ADD COLUMN pais_publicacion CHAR(2) NULL AFTER notas;
        ");

        // 4. Eliminar columnas de autor de libro
        $this->execute("
            ALTER TABLE libro
                DROP COLUMN autor,
                DROP COLUMN autores,
                DROP COLUMN colaboradores;
        ");
    }

    public function down(): void
    {
        // Re-agregar columnas de autor
        $this->execute("
            ALTER TABLE libro
                ADD COLUMN autor VARCHAR(255) NULL AFTER paginas,
                ADD COLUMN autores VARCHAR(255) NULL AFTER autor,
                ADD COLUMN colaboradores VARCHAR(255) NULL AFTER autores;
        ");

        // Eliminar columnas nuevas
        $this->execute("
            ALTER TABLE libro
                DROP COLUMN edicion,
                DROP COLUMN dimensiones,
                DROP COLUMN ilustraciones,
                DROP COLUMN serie,
                DROP COLUMN numero_serie,
                DROP COLUMN notas,
                DROP COLUMN pais_publicacion;
        ");

        // Drop tablas nuevas
        $this->execute("DROP TABLE IF EXISTS libro_persona;");
        $this->execute("DROP TABLE IF EXISTS persona;");
    }
}
