# Arquitectura Docker del Proyecto

Este documento explica la arquitectura de contenedores definida en `docker-compose.yml`.

---

## Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                      Host Machine                            │
│  ┌─────────────────────────────────────────────────────┐    │
│  │            Docker Network: sigb-network              │    │
│  │                                                       │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │    │
│  │  │     web     │  │     db      │  │ phpmyadmin  │ │    │
│  │  │             │  │             │  │             │ │    │
│  │  │  PHP 8.2    │  │  MySQL 8.0  │  │   Web UI    │ │    │
│  │  │  Apache     │←→│  MariaDB    │←→│  Database   │ │    │
│  │  │             │  │             │  │   Manager   │ │    │
│  │  └─────────────┘  └─────────────┘  └─────────────┘ │    │
│  │      ↕ :8080         ↕ :3306          ↕ :8081       │    │
│  └──────│──────────────│─────────────────│─────────────┘    │
│         │              │                 │                   │
└─────────│──────────────│─────────────────│───────────────────┘
          │              │                 │
     localhost:8080  localhost:3306   localhost:8081
```

---

## Servicios Definidos

### 1. Servicio `web` (Aplicación PHP)

```yaml
web:
  build:
    context: .
    dockerfile: Dockerfile
  container_name: sigb-api-web
  ports:
    - "8080:80"
  volumes:
    - ./:/var/www/html
  environment:
    - APACHE_DOCUMENT_ROOT=/var/www/html
  depends_on:
    - db
  networks:
    - sigb-network
```

#### Configuración Explicada

**`build`**
- `context: .` → Usa el directorio actual como contexto
- `dockerfile: Dockerfile` → Usa el Dockerfile del proyecto
- **Por qué**: Construye la imagen personalizada con PHP + Apache + extensiones

**`container_name: sigb-api-web`**
- Nombre fijo del contenedor
- **Ventaja**: Fácil de identificar en `docker ps`
- **Uso**: `docker exec -it sigb-api-web bash`

**`ports: "8080:80"`**
- **8080** → Puerto en tu máquina (host)
- **80** → Puerto dentro del contenedor
- **Resultado**: Accedes en http://localhost:8080
- **Por qué no 80**: El puerto 80 puede estar ocupado en tu máquina

**`volumes: ./:/var/www/html`**
- **./** → Directorio del proyecto en tu máquina
- **/var/www/html** → Directorio dentro del contenedor
- **Resultado**: Cambios en tu código se reflejan inmediatamente
- **Ventaja**: No necesitas reconstruir la imagen al modificar código

**`environment`**
- Variables de entorno para el contenedor
- `APACHE_DOCUMENT_ROOT` → Configura la raíz del servidor web

**`depends_on: - db`**
- El servicio `web` espera a que `db` esté listo
- **Orden de inicio**: primero `db`, luego `web`
- **Nota**: No garantiza que MySQL esté 100% listo, solo que el contenedor inició

**`networks: - sigb-network`**
- Conecta el contenedor a la red interna
- **Ventaja**: Puede comunicarse con `db` y `phpmyadmin`

---

### 2. Servicio `db` (MySQL)

```yaml
db:
  image: mysql:8.0
  container_name: sigb-api-db
  restart: unless-stopped
  environment:
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
    MYSQL_DATABASE: ${DB_DATABASE:-sigb_db}
    MYSQL_USER: ${DB_USERNAME:-sigb_user}
    MYSQL_PASSWORD: ${DB_PASSWORD:-sigb_password}
  ports:
    - "3306:3306"
  volumes:
    - db_data:/var/lib/mysql
  networks:
    - sigb-network
```

#### Configuración Explicada

**`image: mysql:8.0`**
- Usa la imagen oficial de MySQL versión 8.0
- **Por qué 8.0**: Versión estable, ampliamente soportada
- **Alternativa anterior**: Era `mariadb:11.2`, cambiado a MySQL por requerimiento

**`restart: unless-stopped`**
- Si el contenedor se detiene, Docker lo reinicia automáticamente
- **Excepto**: Si lo detienes manualmente con `docker stop`
- **Ventaja**: Resistente a reinicios del sistema

**`environment` (Variables de Entorno)**

| Variable | Valor por Defecto | Propósito |
|----------|-------------------|-----------|
| `MYSQL_ROOT_PASSWORD` | rootpassword | Contraseña del usuario root |
| `MYSQL_DATABASE` | sigb_db | Base de datos que se crea automáticamente |
| `MYSQL_USER` | sigb_user | Usuario de la aplicación |
| `MYSQL_PASSWORD` | sigb_password | Contraseña del usuario de la app |

**Sintaxis `${VAR:-default}`:**
- Lee de `.env` si existe
- Si no, usa el valor por defecto
- **Ventaja**: Flexibilidad sin romper el setup

**`ports: "3306:3306"`**
- Expone MySQL al host
- **Uso**: Puedes conectarte con clientes externos (MySQL Workbench, DBeaver)
- **Conexión**: `mysql -h 127.0.0.1 -P 3306 -u sigb_user -p`

**`volumes: db_data:/var/lib/mysql`**
- **Volumen nombrado**: `db_data` (definido al final)
- **/var/lib/mysql** → Donde MySQL guarda los datos
- **Ventaja**: Los datos persisten aunque borres el contenedor
- **Importante**: Si haces `docker-compose down -v`, SÍ se borran

---

### 3. Servicio `phpmyadmin` (Administrador Web)

```yaml
phpmyadmin:
  image: phpmyadmin:latest
  container_name: sigb-api-phpmyadmin
  restart: unless-stopped
  ports:
    - "8081:80"
  environment:
    PMA_HOST: db
    PMA_PORT: 3306
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
  depends_on:
    - db
  networks:
    - sigb-network
```

#### Configuración Explicada

**`image: phpmyadmin:latest`**
- Imagen oficial de phpMyAdmin
- Herramienta web para administrar MySQL

**`ports: "8081:80"`**
- Acceso: http://localhost:8081
- **Por qué 8081**: 8080 ya está ocupado por la aplicación

**`environment`**
- `PMA_HOST: db` → Se conecta al servicio `db` (nombre del contenedor)
- `PMA_PORT: 3306` → Puerto de MySQL
- `MYSQL_ROOT_PASSWORD` → Para login automático como root

**`depends_on: - db`**
- PHPMyAdmin necesita que MySQL esté corriendo

---

## Volúmenes

```yaml
volumes:
  db_data:
    driver: local
```

### ¿Qué es un Volumen?

- Almacenamiento persistente gestionado por Docker
- Independiente del ciclo de vida del contenedor

### `db_data`

**¿Dónde está físicamente?**
```bash
# Linux
/var/lib/docker/volumes/sigb-api-cresta_db_data/_data

# macOS/Windows (Docker Desktop)
En la VM de Docker
```

**¿Cómo acceder?**
```bash
# Ver volúmenes
docker volume ls

# Inspeccionar
docker volume inspect sigb-api-cresta_db_data

# Backup
docker run --rm -v sigb-api-cresta_db_data:/data -v $(pwd):/backup ubuntu tar czf /backup/db_backup.tar.gz /data
```

**¿Cuándo se borra?**
```bash
# NO borra datos
docker-compose down

# SÍ borra datos
docker-compose down -v
```

---

## Redes

```yaml
networks:
  sigb-network:
    driver: bridge
```

### ¿Qué es una Red?

- Permite que los contenedores se comuniquen entre sí
- Aislamiento del resto de contenedores en tu máquina

### `sigb-network`

**Tipo: bridge**
- Red virtual privada entre contenedores
- Los contenedores pueden comunicarse por nombre

**Comunicación interna:**
```php
// En el código PHP (contenedor 'web')
$host = 'db'; // ← Nombre del servicio, NO 'localhost'
$pdo = new PDO("mysql:host=$host;dbname=sigb_db", ...);
```

**¿Por qué funciona `db` como hostname?**
- Docker resuelve automáticamente `db` → IP del contenedor de MySQL
- DNS interno de Docker

---

## Variables de Entorno (.env)

### Archivo `.env` (debe crear el usuario)

```env
DB_HOST=db
DB_PORT=3306
DB_DATABASE=sigb_db
DB_USERNAME=sigb_user
DB_PASSWORD=sigb_password
DB_ROOT_PASSWORD=rootpassword

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080
```

### ¿Cómo se usan?

**En docker-compose.yml:**
```yaml
MYSQL_DATABASE: ${DB_DATABASE:-sigb_db}
```

**En PHP:**
```php
$_ENV['DB_HOST'] // 'db'
$_ENV['DB_DATABASE'] // 'sigb_db'
```

---

## Flujo de Datos

### 1. Request HTTP

```
Usuario → http://localhost:8080/api/books
         ↓
Host Machine :8080
         ↓
Docker Network
         ↓
Contenedor 'web' :80
         ↓
Apache → index.php
         ↓
PHP procesa request
```

### 2. Consulta a Base de Datos

```
PHP en 'web'
    ↓
PDO: mysql:host=db;dbname=sigb_db
    ↓
Docker DNS resuelve 'db'
    ↓
Contenedor 'db' :3306
    ↓
MySQL procesa query
    ↓
Resultado ← PHP ← Apache ← Usuario
```

### 3. Administración con PHPMyAdmin

```
Usuario → http://localhost:8081
         ↓
Contenedor 'phpmyadmin' :80
         ↓
phpMyAdmin se conecta a 'db':3306
         ↓
Interfaz web para administrar MySQL
```

---

## Comandos Útiles

### Iniciar Servicios
```bash
docker-compose up -d

# Ver logs
docker-compose logs -f

# Solo logs de un servicio
docker-compose logs -f web
```

### Detener Servicios
```bash
# Detiene pero mantiene contenedores
docker-compose stop

# Detiene y elimina contenedores (pero NO datos)
docker-compose down

# Detiene, elimina contenedores Y volúmenes (¡BORRA DATOS!)
docker-compose down -v
```

### Reconstruir Imagen
```bash
# Cuando cambias el Dockerfile
docker-compose up -d --build

# Forzar reconstrucción sin caché
docker-compose build --no-cache web
```

### Acceder a Contenedores
```bash
# Bash en contenedor web
docker-compose exec web bash

# Bash en contenedor db
docker-compose exec db bash

# MySQL CLI
docker-compose exec db mysql -u root -p
```

### Ver Estado
```bash
# Contenedores corriendo
docker-compose ps

# Todos los contenedores
docker ps -a

# Uso de recursos
docker stats
```

### Ejecutar Comandos
```bash
# Composer install
docker-compose exec web composer install

# Tests
docker-compose exec web composer test

# Ver versión PHP
docker-compose exec web php -v
```

---

## Seguridad y Mejores Prácticas

### ✅ Aplicadas

1. **Volúmenes para datos persistentes**: Los datos sobreviven reinicios
2. **Red aislada**: Los contenedores no exponen más de lo necesario
3. **Variables de entorno**: Credenciales configurables
4. **Usuario no-root en contenedores**: Apache corre como `www-data`
5. **Restart policies**: Servicios se recuperan automáticamente

### ⚠️ Para Producción (NO necesario ahora)

1. **Usar Docker secrets** en lugar de variables de entorno
2. **Proxy reverso** (Nginx/Traefik) para SSL
3. **Healthchecks** para verificar que servicios estén realmente listos
4. **Límites de recursos** (CPU, RAM)
5. **No exponer puerto 3306** públicamente

---

## Troubleshooting

### Contenedor no inicia
```bash
# Ver logs de error
docker-compose logs web

# Inspeccionar contenedor
docker inspect sigb-api-web
```

### No puede conectar a MySQL
```bash
# Verificar que esté corriendo
docker-compose ps db

# Probar conexión desde web
docker-compose exec web ping db

# Conectar manualmente
docker-compose exec web mysql -h db -u sigb_user -p
```

### Puerto ocupado
```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "8082:80"  # En lugar de 8080
```

### Permisos en archivos
```bash
# Arreglar permisos
docker-compose exec web chown -R www-data:www-data /var/www/html
```

---

## Resumen

| Servicio | Puerto Host | Puerto Container | Propósito |
|----------|-------------|------------------|-----------|
| web | 8080 | 80 | Aplicación PHP + Apache |
| db | 3306 | 3306 | Base de datos MySQL |
| phpmyadmin | 8081 | 80 | Administrador web de MySQL |

**Todos conectados en la red `sigb-network` con datos persistentes en el volumen `db_data`.**
