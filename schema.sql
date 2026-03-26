-- SIGB - Schema completo para visualización en Vertabelo
-- Generado: 2026-03-26

CREATE TABLE tipo_documento (
    id BIGINT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(3) NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    renovable BOOL NOT NULL DEFAULT 1,
    detalle VARCHAR(100) NULL,
    CONSTRAINT tipo_documento_pk PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE tema (
    id BIGINT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(100) NOT NULL,
    CONSTRAINT tema_pk PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE materia (
    id BIGINT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(100) NOT NULL,
    CONSTRAINT materia_pk PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carrera (
    id BIGINT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(3) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    CONSTRAINT carrera_pk PRIMARY KEY (id),
    UNIQUE INDEX uq_carrera_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permiso (
    id BIGINT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    CONSTRAINT permiso_pk PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role (
    id BIGINT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT role_pk PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user` (
    id BIGINT NOT NULL AUTO_INCREMENT,
    dni VARCHAR(8) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT user_pk PRIMARY KEY (id),
    UNIQUE INDEX uq_user_dni (dni),
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES role (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permiso (
    role_id BIGINT NOT NULL,
    permiso_id BIGINT NOT NULL,
    CONSTRAINT role_permiso_pk PRIMARY KEY (role_id, permiso_id),
    CONSTRAINT fk_role_permiso_role FOREIGN KEY (role_id) REFERENCES role (id),
    CONSTRAINT fk_role_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permiso (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE persona (
    id BIGINT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT persona_pk PRIMARY KEY (id),
    UNIQUE INDEX uq_persona_nombre_apellido (nombre, apellido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articulo (
    id BIGINT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(100) NOT NULL,
    anio_publicacion INT NOT NULL,
    tipo_documento_id BIGINT NOT NULL,
    idioma VARCHAR(2) NOT NULL DEFAULT 'es',
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT articulo_pk PRIMARY KEY (id),
    INDEX idx_articulo_tipo_documento (tipo_documento_id),
    INDEX idx_articulo_anio (anio_publicacion),
    CONSTRAINT fk_articulo_tipo_documento FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    CONSTRAINT libro_pk PRIMARY KEY (articulo_id),
    UNIQUE INDEX uq_libro_isbn (isbn),
    CONSTRAINT fk_libro_articulo FOREIGN KEY (articulo_id) REFERENCES articulo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE libro_persona (
    libro_id BIGINT NOT NULL,
    persona_id BIGINT NOT NULL,
    rol ENUM('autor','coautor','colaborador','editor','traductor','ilustrador') NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT libro_persona_pk PRIMARY KEY (libro_id, persona_id, rol),
    INDEX idx_libro_persona_persona_id (persona_id),
    CONSTRAINT fk_libro_persona_libro FOREIGN KEY (libro_id) REFERENCES libro (articulo_id) ON DELETE CASCADE,
    CONSTRAINT fk_libro_persona_persona FOREIGN KEY (persona_id) REFERENCES persona (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ejemplar (
    id BIGINT NOT NULL AUTO_INCREMENT,
    codigo_barras VARCHAR(13) NULL,
    habilitado BOOL NOT NULL DEFAULT 1,
    articulo_id BIGINT NOT NULL,
    signatura_topografica VARCHAR(200) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ejemplar_pk PRIMARY KEY (id),
    UNIQUE INDEX uq_ejemplar_codigo_barras (codigo_barras),
    INDEX idx_ejemplar_articulo (articulo_id),
    CONSTRAINT fk_ejemplar_articulo FOREIGN KEY (articulo_id) REFERENCES articulo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articulo_tema (
    articulo_id BIGINT NOT NULL,
    tema_id BIGINT NOT NULL,
    CONSTRAINT articulo_tema_pk PRIMARY KEY (articulo_id, tema_id),
    CONSTRAINT fk_articulo_tema_articulo FOREIGN KEY (articulo_id) REFERENCES articulo (id),
    CONSTRAINT fk_articulo_tema_tema FOREIGN KEY (tema_id) REFERENCES tema (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE materia_articulo (
    articulo_id BIGINT NOT NULL,
    materia_id BIGINT NOT NULL,
    CONSTRAINT materia_articulo_pk PRIMARY KEY (articulo_id, materia_id),
    CONSTRAINT fk_materia_articulo_articulo FOREIGN KEY (articulo_id) REFERENCES articulo (id),
    CONSTRAINT fk_materia_articulo_materia FOREIGN KEY (materia_id) REFERENCES materia (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carrera_materia (
    carrera_id BIGINT NOT NULL,
    materia_id BIGINT NOT NULL,
    CONSTRAINT carrera_materia_pk PRIMARY KEY (carrera_id, materia_id),
    CONSTRAINT fk_carrera_materia_carrera FOREIGN KEY (carrera_id) REFERENCES carrera (id),
    CONSTRAINT fk_carrera_materia_materia FOREIGN KEY (materia_id) REFERENCES materia (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    CONSTRAINT lector_pk PRIMARY KEY (id),
    UNIQUE INDEX uq_lector_user_id (user_id),
    UNIQUE INDEX uq_lector_tarjeta_id (tarjeta_id),
    INDEX idx_lector_apellido (apellido),
    CONSTRAINT fk_lector_user FOREIGN KEY (user_id) REFERENCES `user` (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lector_carrera (
    lector_id BIGINT NOT NULL,
    carrera_id BIGINT NOT NULL,
    CONSTRAINT lector_carrera_pk PRIMARY KEY (lector_id, carrera_id),
    CONSTRAINT fk_lector_carrera_lector FOREIGN KEY (lector_id) REFERENCES lector (id),
    CONSTRAINT fk_lector_carrera_carrera FOREIGN KEY (carrera_id) REFERENCES carrera (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    CONSTRAINT prestamo_pk PRIMARY KEY (id),
    INDEX idx_prestamo_lector_estado (lector_id, estado),
    INDEX idx_prestamo_ejemplar_estado (ejemplar_id, estado),
    INDEX idx_prestamo_fecha_vencimiento (fecha_vencimiento),
    CONSTRAINT fk_prestamo_ejemplar FOREIGN KEY (ejemplar_id) REFERENCES ejemplar (id),
    CONSTRAINT fk_prestamo_lector FOREIGN KEY (lector_id) REFERENCES lector (id),
    CONSTRAINT fk_prestamo_tipo_prestamo FOREIGN KEY (tipo_prestamo_id) REFERENCES tipo_prestamo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    CONSTRAINT reserva_pk PRIMARY KEY (id),
    INDEX idx_reserva_lector_estado (lector_id, estado),
    INDEX idx_reserva_ejemplar_estado (ejemplar_id, estado),
    INDEX idx_reserva_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_reserva_articulo_estado_fecha (articulo_id, estado, fecha_reserva),
    CONSTRAINT fk_reserva_articulo FOREIGN KEY (articulo_id) REFERENCES articulo (id),
    CONSTRAINT fk_reserva_lector FOREIGN KEY (lector_id) REFERENCES lector (id),
    CONSTRAINT fk_reserva_ejemplar FOREIGN KEY (ejemplar_id) REFERENCES ejemplar (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
