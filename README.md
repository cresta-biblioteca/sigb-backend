# SIGB API - Sistema de Gestión Bibliotecaria

API REST para el Sistema de Gestión Bibliotecaria desarrollado como proyecto de prácticas profesionales.

## Tecnologías Utilizadas

- **PHP 8.3** con Apache2
- **MySQL 8.0** - Base de datos
- **Composer** - Gestor de dependencias
- **Pest/PHPUnit** - Framework de testing
- **Phinx** - Migraciones de base de datos
- **Docker** - Contenedorización
- **Git** - Control de versiones
- **GitHub Actions** - CI/CD

## Requisitos Previos

- Docker
- Docker Compose
- Git

## Instalación

1. Clonar el repositorio:
```bash
git clone <url-del-repositorio>
cd sigb-api-cresta
```

2. Copiar el archivo de variables de entorno:
```bash
cp .env.example .env
```

3. Construir y levantar los contenedores:
```bash
docker-compose up -d --build
```

4. Instalar dependencias de PHP:
```bash
docker-compose exec web composer install
```

## Servicios Disponibles

- **API**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306

### Credenciales de Base de Datos

- **Usuario**: sigb_user
- **Contraseña**: secret
- **Base de datos**: sigb_db
- **Root Password**: secret

## Estructura del Proyecto

```
sigb-api-cresta/
├── config/           # Archivos de configuración
│   ├── database.php  # Configuración de base de datos
│   └── routes.php    # Definición de rutas
├── public/           # Directorio público (Document Root)
│   ├── .htaccess     # Configuración de Apache
│   └── index.php     # Punto de entrada de la aplicación
├── src/              # Código fuente de la aplicación
├── tests/            # Tests unitarios e integración
│   ├── Unit/         # Tests unitarios
│   ├── Integration/  # Tests de integración
│   ├── Pest.php      # Configuración de Pest
│   └── TestCase.php  # Clase base para tests
├── vendor/           # Dependencias de Composer (no versionado)
├── .env              # Variables de entorno (no versionado)
├── .env.example      # Ejemplo de variables de entorno
├── composer.json     # Configuración de Composer
├── Dockerfile        # Configuración de Docker
└── docker-compose.yml # Orquestación de contenedores
```

## Testing

Ejecutar todos los tests:
```bash
docker-compose exec web composer test
```

Ejecutar solo tests unitarios:
```bash
docker-compose exec web composer test:unit
```

Ejecutar solo tests de integración:
```bash
docker-compose exec web composer test:integration
```

Ejecutar tests con cobertura:
```bash
docker-compose exec web composer test:coverage
```

## Comandos Útiles

### Docker

```bash
# Iniciar servicios
docker-compose up -d

# Detener servicios
docker-compose down

# Ver logs
docker-compose logs -f

# Acceder al contenedor web
docker-compose exec web bash

# Reconstruir contenedores
docker-compose up -d --build
```

### Composer

```bash
# Instalar dependencias
docker-compose exec web composer install

# Actualizar dependencias
docker-compose exec web composer update

# Agregar paquete
docker-compose exec web composer require <paquete>
```

### Base de Datos

```bash
# Acceder a MySQL
docker-compose exec db mysql -u sigb_user -p sigb_db

# Ejecutar migraciones
docker-compose exec web composer migrate

# Revertir última migración
docker-compose exec web composer migrate:rollback

# Crear nueva migración
docker-compose exec web composer migrate:create NombreMigracion
```

## CI/CD

El proyecto utiliza GitHub Actions para integración continua. En cada push o PR a las ramas `main` y `develop` se ejecuta:

1. **Validación de sintaxis PHP** - Verifica errores de sintaxis en todos los archivos .php
2. **Validación de estándares de código** - Verifica cumplimiento de PSR-12 en el directorio `src/`
3. **Tests automatizados** - Ejecuta la suite de tests (cuando estén disponibles)

El workflow se encuentra en `.github/workflows/php-cli.yml`

## Desarrollo

1. El código de la aplicación se encuentra en la carpeta `src/`
2. El punto de entrada es `public/index.php`
3. Los archivos de configuración están en `config/`
4. Los tests deben ubicarse en `tests/Unit/` o `tests/Integration/` según corresponda
5. Seguir el estándar PSR-4 para autoloading (namespace `App\` mapea a `src/`)
6. Seguir el estándar PSR-12 para estilo de código
7. Escribir tests para módulos críticos usando Pest

## Contribuir

1. Crear una rama feature: `git checkout -b feature/nueva-funcionalidad`
2. Realizar commits descriptivos
3. Ejecutar tests antes de hacer push
4. Crear Pull Request

## Glosario de Términos

- **PSR-4**: Estándar de autoloading de PHP que define cómo mapear namespaces a la estructura de directorios. En este proyecto, `App\` mapea a `src/`.

- **PSR-12**: Estándar de estilo de código extendido que define reglas de formato para código PHP (indentación, llaves, espacios, etc.). Validado automáticamente en CI.

- **Pest**: Framework de testing moderno para PHP, construido sobre PHPUnit, con sintaxis más expresiva y legible. Utiliza funciones como `test()` y `expect()`.

- **Phinx**: Herramienta de migraciones de base de datos que permite versionar y aplicar cambios en el esquema de forma controlada y reversible.

- **Composer**: Gestor de dependencias para PHP que maneja la instalación de librerías y el autoloading de clases según PSR-4.

- **Docker Compose**: Herramienta para definir y ejecutar aplicaciones Docker multi-contenedor. En este proyecto orquesta los servicios web, db y phpmyadmin.

- **PHPMyAdmin**: Interfaz web para administrar bases de datos MySQL/MariaDB, incluida para facilitar la gestión de datos en desarrollo.

- **CI/CD**: Integración Continua y Entrega Continua. GitHub Actions ejecuta automáticamente validaciones y tests en cada push/PR.

## Licencia

Este proyecto es parte de prácticas profesionales.
