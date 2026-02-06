# Registro de Cambios del Setup Inicial

Este documento lista todos los archivos creados y modificados durante el setup inicial del proyecto.

---

## Fecha

**6 de Febrero de 2026**

---

## Resumen Ejecutivo

Se configuró el entorno de desarrollo completo con:
- Docker + Docker Compose
- PHP 8.2 + Apache2
- MySQL 8.0
- Composer con autoloading PSR-4
- Pest/PHPUnit para testing
- Estructura de directorios profesional

---

## Archivos Creados

### 1. Configuración de Docker

#### `docker-compose.yml` ✅ NUEVO
- Orquestación de 3 servicios: web, db, phpmyadmin
- Red interna: sigb-network
- Volumen persistente para MySQL
- Configuración de variables de entorno

**Servicios configurados:**
- `web`: PHP 8.2 + Apache en puerto 8080
- `db`: MySQL 8.0 en puerto 3306 (cambiado de MariaDB 11.2)
- `phpmyadmin`: Administrador web en puerto 8081

---

### 2. Configuración de Entorno

#### `.env.example` ✅ NUEVO
Variables de entorno de ejemplo:
- Credenciales de MySQL
- Configuración de la aplicación
- Puertos de servicios

**Usuario debe copiar a `.env`:**
```bash
cp .env.example .env
```

#### `.gitignore` ✅ NUEVO
Excluye de Git:
- `.env` (credenciales)
- `vendor/` (dependencias)
- `.idea/` (configuración IDE)
- Caché y logs

---

### 3. Configuración de Testing

#### `phpunit.xml` ✅ NUEVO
- Configuración de PHPUnit
- Testsuites: Unit e Integration
- Cobertura de código configurada
- Bootstrap: vendor/autoload.php

#### `tests/Pest.php` ✅ NUEVO
- Archivo de configuración de Pest
- Setup inicial del framework de testing
- Placeholder para customs expectations y helpers

#### `tests/Unit/ExampleTest.php` ✅ NUEVO
Tests de ejemplo:
- Test básico de expectativas
- Test de operaciones matemáticas
- Test de manipulación de arrays

**Propósito**: Verificar que Pest está correctamente instalado

---

### 4. Código Fuente

#### `src/Database/Connection.php` ✅ NUEVO
Clase para conexión a MySQL:
- Patrón Singleton
- Usa PDO con prepared statements
- Lee variables de entorno
- Manejo de errores con excepciones

**Características:**
- `Connection::getInstance()` → Obtiene instancia única
- Configuración desde `.env`
- Previene clonación y deserialización

---

### 5. Documentación

#### `README.md` ✅ NUEVO
Documentación completa del proyecto:
- Tecnologías utilizadas
- Instrucciones de instalación
- Servicios disponibles y puertos
- Estructura del proyecto
- Comandos útiles (Docker, Composer, Testing)
- Guía de contribución

#### `docs/setup-inicial/README.md` ✅ NUEVO
Índice de la documentación técnica del setup inicial

#### `docs/setup-inicial/dockerfile-explicado.md` ✅ NUEVO
Explicación detallada del Dockerfile:
- Imagen base y por qué se eligió
- Cada extensión PHP instalada
- Configuración de Apache
- Mejores prácticas aplicadas

#### `docs/setup-inicial/dependencias-composer.md` ✅ NUEVO
Documentación de composer.json:
- Cada dependencia y su propósito
- Diferencia entre require y require-dev
- Scripts de testing
- Autoloading PSR-4

#### `docs/setup-inicial/arquitectura-docker.md` ✅ NUEVO
Arquitectura de contenedores:
- Diagrama de servicios
- Configuración de cada servicio
- Volúmenes y redes
- Flujo de datos
- Comandos útiles y troubleshooting

#### `docs/setup-inicial/cambios-realizados.md` ✅ NUEVO (este archivo)
Registro completo de cambios realizados

---

## Archivos Modificados

### 1. `Dockerfile` 🔄 MODIFICADO

**Estado anterior:**
```dockerfile
FROM ubuntu:latest
LABEL authors="mateo"
ENTRYPOINT ["top", "-b"]
```

**Estado actual:**
- Imagen base: `php:8.2-apache`
- Extensiones instaladas: PDO, PDO_MySQL, MySQLi, Zip, GD
- Git + Composer instalados
- mod_rewrite habilitado
- Permisos configurados
- DocumentRoot configurable

**Cambios clave:**
- ❌ Eliminado: Ubuntu base genérico
- ✅ Agregado: PHP 8.2 + Apache preconfigurado
- ✅ Agregado: Todas las extensiones necesarias
- ✅ Agregado: Herramientas de desarrollo

---

### 2. `composer.json` 🔄 MODIFICADO

**Estado anterior:**
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

**Estado actual:**
- Metadata del proyecto agregada
- Dependencias de producción: PHP 8.2, PDO, MySQLi, JSON
- Dependencias de desarrollo: Pest 2.34, PHPUnit 10.5
- Autoload dev: Tests namespace
- Scripts para testing
- Configuración optimizada

**Cambios clave:**
- ✅ Agregado: Testing framework (Pest/PHPUnit)
- ✅ Agregado: Scripts de testing
- ✅ Agregado: Extensiones requeridas
- ✅ Agregado: Configuración de autoloader

---

### 3. `index.php` ⏸️ NO MODIFICADO

**Estado:** Mantenido original (modificación rechazada por usuario)

**Nota:** El archivo se mantendrá sin cambios hasta nueva instrucción.

---

## Estructura de Directorios Creada

```
sigb-api-cresta/
├── docs/
│   └── setup-inicial/          # ✅ NUEVO
│       ├── README.md
│       ├── arquitectura-docker.md
│       ├── cambios-realizados.md
│       ├── dependencias-composer.md
│       └── dockerfile-explicado.md
├── src/
│   └── Database/               # ✅ NUEVO
│       └── Connection.php
├── tests/
│   ├── Integration/            # ✅ NUEVO (vacío por ahora)
│   ├── Unit/                   # ✅ NUEVO
│   │   └── ExampleTest.php
│   └── Pest.php                # ✅ NUEVO
├── .env.example                # ✅ NUEVO
├── .gitignore                  # ✅ NUEVO
├── composer.json               # 🔄 MODIFICADO
├── docker-compose.yml          # ✅ NUEVO
├── Dockerfile                  # 🔄 MODIFICADO
├── phpunit.xml                 # ✅ NUEVO
└── README.md                   # ✅ NUEVO
```

---

## Tecnologías Implementadas

### ✅ Requerimientos Cumplidos

| Requerimiento | Tecnología Implementada | Estado |
|---------------|------------------------|--------|
| Base de datos | MySQL 8.0 | ✅ Implementado |
| Servidor web | Apache2 (vía php:8.2-apache) | ✅ Implementado |
| Herramientas | Git + Gestor de issues (GitHub) | ✅ Implementado |
| Testing | Pest + PHPUnit | ✅ Implementado |

### Tecnologías Adicionales

- **Docker**: Contenedorización y entorno reproducible
- **Docker Compose**: Orquestación de servicios
- **Composer**: Gestor de dependencias PHP
- **PDO**: Capa de abstracción de base de datos
- **PSR-4**: Autoloading estándar

---

## Configuración de Base de Datos

### Cambio Importante: MariaDB → MySQL

**Inicial:** `mariadb:11.2`
**Final:** `mysql:8.0`

**Razón del cambio:** Requerimiento específico de usar MySQL en las prácticas profesionales.

### Credenciales por Defecto

```
Host: db (localhost en host machine)
Puerto: 3306
Database: sigb_db
Usuario: sigb_user
Contraseña: sigb_password
Root Password: rootpassword
```

⚠️ **Importante**: Estas son credenciales de desarrollo. Cambiar para producción.

---

## Próximos Pasos Recomendados

### Configuración Inicial

1. **Copiar archivo de entorno:**
   ```bash
   cp .env.example .env
   ```

2. **Iniciar servicios Docker:**
   ```bash
   docker-compose up -d --build
   ```

3. **Instalar dependencias:**
   ```bash
   docker-compose exec web composer install
   ```

4. **Verificar instalación:**
   ```bash
   docker-compose exec web composer test
   ```

### Desarrollo

5. **Crear estructura de base de datos:**
   - Tablas necesarias (libros, usuarios, préstamos, etc.)
   - Migrations o scripts SQL

6. **Implementar endpoints de API:**
   - Sistema de routing
   - Controladores
   - Modelos

7. **Escribir tests:**
   - Tests unitarios para lógica de negocio
   - Tests de integración para base de datos
   - Tests de API para endpoints

8. **Configurar Git:**
   - Inicializar repositorio si no existe
   - Configurar gestor de issues (GitHub)
   - Establecer workflow de desarrollo

---

## Comandos de Verificación

### Verificar que todo funciona:

```bash
# 1. Contenedores corriendo
docker-compose ps

# 2. Acceder a la aplicación
curl http://localhost:8080

# 3. Verificar conexión a MySQL
docker-compose exec web php -r "new PDO('mysql:host=db;dbname=sigb_db', 'sigb_user', 'sigb_password'); echo 'OK';"

# 4. Ejecutar tests
docker-compose exec web composer test

# 5. PHPMyAdmin accesible
# Abrir: http://localhost:8081
```

---

## Notas de Seguridad

### ⚠️ Solo para Desarrollo

Este setup está optimizado para **desarrollo local**, NO para producción.

**En producción deberías:**
- Usar secretos de Docker en lugar de variables de entorno
- No exponer puerto 3306 públicamente
- Usar HTTPS con certificados SSL
- Implementar rate limiting
- Configurar firewall
- Cambiar todas las contraseñas por defecto
- Usar imagen de producción (sin herramientas de desarrollo)

---

## Soporte y Contacto

Si encuentras problemas:
1. Revisar logs: `docker-compose logs -f`
2. Consultar la documentación en `docs/setup-inicial/`
3. Verificar que los puertos 8080, 3306 y 8081 no estén ocupados
4. Crear issue en el repositorio

---

## Changelog

### v1.0.0 - 2026-02-06

**Agregado:**
- Configuración completa de Docker
- Framework de testing (Pest/PHPUnit)
- Documentación técnica detallada
- Estructura de directorios profesional
- Clase de conexión a base de datos

**Modificado:**
- Dockerfile: De Ubuntu genérico a PHP 8.2 + Apache
- composer.json: Agregadas dependencias y scripts
- Base de datos: De MariaDB 11.2 a MySQL 8.0

**Pendiente:**
- index.php: Esperando aprobación para modificar
- Schema de base de datos
- Endpoints de API
- Tests de integración

---

## Licencia

Este proyecto es parte de prácticas profesionales.

---

## Contribuciones

**Desarrolladores:**
- Setup inicial: Claude Code
- Supervisión: Mateo

**Fecha:** 6 de Febrero de 2026
