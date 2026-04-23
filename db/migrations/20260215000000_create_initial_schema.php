<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    public function up(): void
    {
        // ============================================================
        // TABLAS DE REFERENCIA / CONFIGURACIÓN
        // ============================================================

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
        // TABLAS DE PERSONAS (autores, colaboradores, etc.)
        // ============================================================

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

        // ============================================================
        // TABLAS DE CATÁLOGO
        // tipo VARCHAR(30): valores según MARC21 (libro, revista, tesis, mapa, partitura)
        // ============================================================

        $this->execute("
            CREATE TABLE articulo (
                id BIGINT NOT NULL AUTO_INCREMENT,
                titulo VARCHAR(100) NOT NULL,
                anio_publicacion INT NOT NULL,
                tipo VARCHAR(30) NOT NULL,
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
                titulo_informativo VARCHAR(255) NULL,
                cdu INT NULL,
                editorial VARCHAR(200) NULL,
                lugar_de_publicacion VARCHAR(200) NULL,
                edicion VARCHAR(100) NULL,
                dimensiones VARCHAR(50) NULL,
                ilustraciones VARCHAR(100) NULL,
                serie VARCHAR(255) NULL,
                numero_serie VARCHAR(50) NULL,
                notas TEXT NULL,
                pais_publicacion CHAR(2) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX uq_libro_isbn (isbn),
                CONSTRAINT libro_pk PRIMARY KEY (articulo_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

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
                cant_renovaciones INT UNSIGNED NOT NULL DEFAULT 0,
                max_renovaciones INT UNSIGNED NOT NULL DEFAULT 0,
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

        $this->execute("CREATE INDEX idx_articulo_tipo ON articulo (tipo);");
        $this->execute("CREATE INDEX idx_articulo_anio ON articulo (anio_publicacion);");

        $this->execute("CREATE INDEX idx_lector_apellido ON lector (apellido);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS reserva;");
        $this->execute("DROP TABLE IF EXISTS prestamo;");
        $this->execute("DROP TABLE IF EXISTS lector_carrera;");
        $this->execute("DROP TABLE IF EXISTS lector;");
        $this->execute("DROP TABLE IF EXISTS articulo_tema;");
        $this->execute("DROP TABLE IF EXISTS ejemplar;");
        $this->execute("DROP TABLE IF EXISTS libro_persona;");
        $this->execute("DROP TABLE IF EXISTS libro;");
        $this->execute("DROP TABLE IF EXISTS persona;");
        $this->execute("DROP TABLE IF EXISTS articulo;");
        $this->execute("DROP TABLE IF EXISTS role_permiso;");
        $this->execute("DROP TABLE IF EXISTS `user`;");
        $this->execute("DROP TABLE IF EXISTS role;");
        $this->execute("DROP TABLE IF EXISTS permiso;");
        $this->execute("DROP TABLE IF EXISTS carrera;");
        $this->execute("DROP TABLE IF EXISTS tema;");
        $this->execute("DROP TABLE IF EXISTS tipo_prestamo;");
    }
}
