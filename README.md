# SIGB API - Sistema de Gestión Bibliotecaria

API REST para el Sistema de Gestión Bibliotecaria desarrollado como proyecto de prácticas profesionales.

## Tecnologías Utilizadas

- **PHP 8.2** con Apache2
- **MariaDB 11.2** - Base de datos
- **Composer** - Gestor de dependencias
- **Pest/PHPUnit** - Framework de testing
- **Docker** - Contenedorización
- **Git** - Control de versiones

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
- **MariaDB**: localhost:3306

### Credenciales de Base de Datos

- **Usuario**: sigb_user
- **Contraseña**: sigb_password
- **Base de datos**: sigb_db
- **Root Password**: rootpassword

## Estructura del Proyecto

```
sigb-api-cresta/
├── src/              # Código fuente de la aplicación
├── tests/            # Tests unitarios e integración
├── vendor/           # Dependencias de Composer
├── .env              # Variables de entorno (no versionado)
├── .env.example      # Ejemplo de variables de entorno
├── composer.json     # Configuración de Composer
├── Dockerfile        # Configuración de Docker
├── docker-compose.yml # Orquestación de contenedores
└── index.php         # Punto de entrada
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
# Acceder a MariaDB
docker-compose exec db mysql -u sigb_user -p sigb_db
```

## Desarrollo

1. El código de la aplicación se encuentra en la carpeta `src/`
2. Los tests deben ubicarse en la carpeta `tests/`
3. Seguir el estándar PSR-4 para autoloading
4. Escribir tests para módulos críticos

## Contribuir

1. Crear una rama feature: `git checkout -b feature/nueva-funcionalidad`
2. Realizar commits descriptivos
3. Ejecutar tests antes de hacer push
4. Crear Pull Request

## Licencia

Este proyecto es parte de prácticas profesionales.
