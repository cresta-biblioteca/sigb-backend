<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    /**
     * Migrate Up - Crea todas las tablas del esquema inicial.
     *
     */
    public function up(): void
    {
        // ============================================================
        // TABLAS DE REFERENCIA / CONFIGURACIÓN
        // ============================================================

        $this->execute("
            CREATE TABLE tipo_documento (
                id BIGINT NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(3) NOT NULL,
                descripcion VARCHAR(100) NOT NULL,
                renovable BOOL NOT NULL DEFAULT 1,
                detalle VARCHAR(100) NULL,
                CONSTRAINT tipo_documento_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE tipo_prestamo (
                id BIGINT NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(3) NOT NULL,
                descripcion VARCHAR(100) NULL,
                max_cantidad_prestamos INT NOT NULL,
                duracion_prestamo INT NOT NULL,
                renovaciones INT NOT NULL,
                dias_renovacion INT NOT NULL,
                cant_dias_renovar INT NOT NULL,
                habilitado BOOL NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT tipo_prestamo_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE tema (
                id BIGINT NOT NULL AUTO_INCREMENT,
                titulo VARCHAR(100) NOT NULL,
                CONSTRAINT tema_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE materia (
                id BIGINT NOT NULL AUTO_INCREMENT,
                titulo VARCHAR(100) NOT NULL,
                CONSTRAINT materia_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE carrera (
                id BIGINT NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(3) NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                UNIQUE INDEX uq_carrera_codigo (codigo),
                CONSTRAINT carrera_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE permiso (
                id BIGINT NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(255) NOT NULL,
                descripcion VARCHAR(255) NOT NULL,
                CONSTRAINT permiso_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE role (
                id BIGINT NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                descripcion VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT role_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ============================================================
        // TABLAS DE SEGURIDAD / USUARIOS
        // ============================================================

        $this->execute("
            CREATE TABLE `user` (
                id BIGINT NOT NULL AUTO_INCREMENT,
                dni VARCHAR(8) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role_id BIGINT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX uq_user_dni (dni),
                CONSTRAINT user_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE role_permiso (
                role_id BIGINT NOT NULL,
                permiso_id BIGINT NOT NULL,
                CONSTRAINT role_permiso_pk PRIMARY KEY (role_id, permiso_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ============================================================
        // TABLAS DE CATÁLOGO
        // ============================================================

        $this->execute("
            CREATE TABLE articulo (
                id BIGINT NOT NULL AUTO_INCREMENT,
                titulo VARCHAR(100) NOT NULL,
                anio_publicacion INT NOT NULL,
                tipo_documento_id BIGINT NOT NULL,
                idioma VARCHAR(2) NOT NULL DEFAULT 'es',
                descripcion VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT articulo_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE libro (
                articulo_id BIGINT NOT NULL,
                isbn VARCHAR(13) NULL,
                issn VARCHAR(8) NULL,
                paginas INT NULL,
                autor VARCHAR(255) NULL,
                autores VARCHAR(255) NULL,
                colaboradores VARCHAR(255) NULL,
                titulo_informativo VARCHAR(255) NULL,
                cdu INT NULL,
                export_marc TEXT NULL,
                editorial VARCHAR(200) NULL,
                lugar_de_publicacion VARCHAR(200) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX uq_libro_isbn (isbn),
                CONSTRAINT libro_pk PRIMARY KEY (articulo_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE ejemplar (
                id BIGINT NOT NULL AUTO_INCREMENT,
                codigo_barras VARCHAR(13) NULL,
                habilitado BOOL NOT NULL DEFAULT 1,
                articulo_id BIGINT NOT NULL,
                signatura_topografica VARCHAR(200) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX uq_ejemplar_codigo_barras (codigo_barras),
                CONSTRAINT ejemplar_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE articulo_tema (
                articulo_id BIGINT NOT NULL,
                tema_id BIGINT NOT NULL,
                CONSTRAINT articulo_tema_pk PRIMARY KEY (articulo_id, tema_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE materia_articulo (
                articulo_id BIGINT NOT NULL,
                materia_id BIGINT NOT NULL,
                CONSTRAINT materia_articulo_pk PRIMARY KEY (articulo_id, materia_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE carrera_materia (
                carrera_id BIGINT NOT NULL,
                materia_id BIGINT NOT NULL,
                CONSTRAINT carrera_materia_pk PRIMARY KEY (carrera_id, materia_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ============================================================
        // TABLAS DE LECTORES
        // ============================================================

        $this->execute("
            CREATE TABLE lector (
                id BIGINT NOT NULL AUTO_INCREMENT,
                tarjeta_id VARCHAR(6) NOT NULL,
                user_id BIGINT NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                apellido VARCHAR(100) NOT NULL,
                legajo VARCHAR(8) NULL,
                genero CHAR(1) NULL,
                fecha_nacimiento DATE NOT NULL,
                telefono VARCHAR(50) NOT NULL,
                email VARCHAR(255) NOT NULL,
                cresta_id BIGINT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX uq_lector_user_id (user_id),
                UNIQUE INDEX uq_lector_tarjeta_id (tarjeta_id),
                CONSTRAINT lector_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE lector_carrera (
                lector_id BIGINT NOT NULL,
                carrera_id BIGINT NOT NULL,
                CONSTRAINT lector_carrera_pk PRIMARY KEY (lector_id, carrera_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ============================================================
        // TABLAS DE CIRCULACIÓN
        // ============================================================

        $this->execute("
            CREATE TABLE prestamo (
                id BIGINT NOT NULL AUTO_INCREMENT,
                fecha_prestamo DATETIME NOT NULL,
                fecha_vencimiento DATETIME NOT NULL,
                fecha_devolucion DATETIME NULL DEFAULT NULL,
                estado VARCHAR(50) NOT NULL,
                tipo_prestamo_id BIGINT NOT NULL,
                ejemplar_id BIGINT NOT NULL,
                lector_id BIGINT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT prestamo_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->execute("
            CREATE TABLE reserva (
                id BIGINT NOT NULL AUTO_INCREMENT,
                fecha_reserva DATETIME NOT NULL,
                fecha_vencimiento DATETIME NULL DEFAULT NULL,
                estado VARCHAR(50) NOT NULL,
                lector_id BIGINT NOT NULL,
                articulo_id BIGINT NOT NULL,
                ejemplar_id BIGINT NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT reserva_pk PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ============================================================
        // FOREIGN KEYS
        // ============================================================

        // user -> role
        $this->execute("
            ALTER TABLE `user` ADD CONSTRAINT fk_user_role
                FOREIGN KEY (role_id) REFERENCES role (id);
        ");

        // role_permiso -> role, permiso
        $this->execute("
            ALTER TABLE role_permiso ADD CONSTRAINT fk_role_permiso_role
                FOREIGN KEY (role_id) REFERENCES role (id);
        ");
        $this->execute("
            ALTER TABLE role_permiso ADD CONSTRAINT fk_role_permiso_permiso
                FOREIGN KEY (permiso_id) REFERENCES permiso (id);
        ");

        // articulo -> tipo_documento
        $this->execute("
            ALTER TABLE articulo ADD CONSTRAINT fk_articulo_tipo_documento
                FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento (id);
        ");

        // libro -> articulo
        $this->execute("
            ALTER TABLE libro ADD CONSTRAINT fk_libro_articulo
                FOREIGN KEY (articulo_id) REFERENCES articulo (id);
        ");

        // ejemplar -> articulo
        $this->execute("
            ALTER TABLE ejemplar ADD CONSTRAINT fk_ejemplar_articulo
                FOREIGN KEY (articulo_id) REFERENCES articulo (id);
        ");

        // articulo_tema -> articulo, tema
        $this->execute("
            ALTER TABLE articulo_tema ADD CONSTRAINT fk_articulo_tema_articulo
                FOREIGN KEY (articulo_id) REFERENCES articulo (id);
        ");
        $this->execute("
            ALTER TABLE articulo_tema ADD CONSTRAINT fk_articulo_tema_tema
                FOREIGN KEY (tema_id) REFERENCES tema (id);
        ");

        // materia_articulo -> articulo, materia
        $this->execute("
            ALTER TABLE materia_articulo ADD CONSTRAINT fk_materia_articulo_articulo
                FOREIGN KEY (articulo_id) REFERENCES articulo (id);
        ");
        $this->execute("
            ALTER TABLE materia_articulo ADD CONSTRAINT fk_materia_articulo_materia
                FOREIGN KEY (materia_id) REFERENCES materia (id);
        ");

        // carrera_materia -> carrera, materia
        $this->execute("
            ALTER TABLE carrera_materia ADD CONSTRAINT fk_carrera_materia_carrera
                FOREIGN KEY (carrera_id) REFERENCES carrera (id);
        ");
        $this->execute("
            ALTER TABLE carrera_materia ADD CONSTRAINT fk_carrera_materia_materia
                FOREIGN KEY (materia_id) REFERENCES materia (id);
        ");

        // lector -> user
        $this->execute("
            ALTER TABLE lector ADD CONSTRAINT fk_lector_user
                FOREIGN KEY (user_id) REFERENCES `user` (id);
        ");

        // lector_carrera -> lector, carrera
        $this->execute("
            ALTER TABLE lector_carrera ADD CONSTRAINT fk_lector_carrera_lector
                FOREIGN KEY (lector_id) REFERENCES lector (id);
        ");
        $this->execute("
            ALTER TABLE lector_carrera ADD CONSTRAINT fk_lector_carrera_carrera
                FOREIGN KEY (carrera_id) REFERENCES carrera (id);
        ");

        // prestamo -> ejemplar, lector, tipo_prestamo
        $this->execute("
            ALTER TABLE prestamo ADD CONSTRAINT fk_prestamo_ejemplar
                FOREIGN KEY (ejemplar_id) REFERENCES ejemplar (id);
        ");
        $this->execute("
            ALTER TABLE prestamo ADD CONSTRAINT fk_prestamo_lector
                FOREIGN KEY (lector_id) REFERENCES lector (id);
        ");
        $this->execute("
            ALTER TABLE prestamo ADD CONSTRAINT fk_prestamo_tipo_prestamo
                FOREIGN KEY (tipo_prestamo_id) REFERENCES tipo_prestamo (id);
        ");

        // reserva -> articulo, lector, ejemplar
        $this->execute("
            ALTER TABLE reserva ADD CONSTRAINT fk_reserva_articulo
                FOREIGN KEY (articulo_id) REFERENCES articulo (id);
        ");
        $this->execute("
            ALTER TABLE reserva ADD CONSTRAINT fk_reserva_lector
                FOREIGN KEY (lector_id) REFERENCES lector (id);
        ");
        $this->execute("
            ALTER TABLE reserva ADD CONSTRAINT fk_reserva_ejemplar
                FOREIGN KEY (ejemplar_id) REFERENCES ejemplar (id);
        ");

        // ============================================================
        // ÍNDICES DE RENDIMIENTO
        // ============================================================

        $this->execute("CREATE INDEX idx_prestamo_lector_estado ON prestamo (lector_id, estado);");
        $this->execute("CREATE INDEX idx_prestamo_ejemplar_estado ON prestamo (ejemplar_id, estado);");
        $this->execute("CREATE INDEX idx_prestamo_fecha_vencimiento ON prestamo (fecha_vencimiento);");

        $this->execute("CREATE INDEX idx_reserva_lector_estado ON reserva (lector_id, estado);");
        $this->execute("CREATE INDEX idx_reserva_ejemplar_estado ON reserva (ejemplar_id, estado);");
        $this->execute("CREATE INDEX idx_reserva_fecha_vencimiento ON reserva (fecha_vencimiento);");
        $this->execute("CREATE INDEX idx_reserva_articulo_estado_fecha ON reserva (articulo_id, estado, fecha_reserva);");

        $this->execute("CREATE INDEX idx_ejemplar_articulo ON ejemplar (articulo_id);");

        $this->execute("CREATE INDEX idx_articulo_tipo_documento ON articulo (tipo_documento_id);");
        $this->execute("CREATE INDEX idx_articulo_anio ON articulo (anio_publicacion);");

        $this->execute("CREATE INDEX idx_lector_apellido ON lector (apellido);");
        $this->execute("CREATE INDEX idx_libro_autor ON libro (autor);");
    }

    /**
     * Migrate Down - Elimina todo el esquema.
     *
    */
    public function down(): void
    {
        // Eliminar en orden inverso por las FK
        $this->execute("DROP TABLE IF EXISTS reserva;");
        $this->execute("DROP TABLE IF EXISTS prestamo;");
        $this->execute("DROP TABLE IF EXISTS lector_carrera;");
        $this->execute("DROP TABLE IF EXISTS lector;");
        $this->execute("DROP TABLE IF EXISTS carrera_materia;");
        $this->execute("DROP TABLE IF EXISTS materia_articulo;");
        $this->execute("DROP TABLE IF EXISTS articulo_tema;");
        $this->execute("DROP TABLE IF EXISTS ejemplar;");
        $this->execute("DROP TABLE IF EXISTS libro;");
        $this->execute("DROP TABLE IF EXISTS articulo;");
        $this->execute("DROP TABLE IF EXISTS role_permiso;");
        $this->execute("DROP TABLE IF EXISTS `user`;");
        $this->execute("DROP TABLE IF EXISTS role;");
        $this->execute("DROP TABLE IF EXISTS permiso;");
        $this->execute("DROP TABLE IF EXISTS carrera;");
        $this->execute("DROP TABLE IF EXISTS materia;");
        $this->execute("DROP TABLE IF EXISTS tema;");
        $this->execute("DROP TABLE IF EXISTS tipo_prestamo;");
        $this->execute("DROP TABLE IF EXISTS tipo_documento;");
    }
}
