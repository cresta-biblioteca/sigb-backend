# Explicación del Dockerfile

Este documento explica en detalle cada sección del Dockerfile y las razones técnicas detrás de cada decisión.

## Estructura General

```dockerfile
FROM php:8.2-apache
```

### Imagen Base: php:8.2-apache

**¿Qué es?**
- Imagen oficial de PHP mantenida por Docker
- Incluye Apache2 preconfigurado y optimizado
- Basada en Debian (estable y bien documentada)

**¿Por qué esta imagen?**
- ✅ Cumple con el requerimiento de Apache2
- ✅ PHP 8.2 es moderno, estable y con mejor rendimiento
- ✅ No necesitas configurar Apache manualmente
- ✅ Optimizada para producción
- ✅ Actualizaciones de seguridad regulares

**Alternativas descartadas:**
- `ubuntu:latest + instalación manual`: Más trabajo, menos optimizado
- `nginx + php-fpm`: No cumple con el requerimiento de Apache2

---

## Instalación de Dependencias del Sistema

```dockerfile
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev
```

### Paquetes Instalados

| Paquete | Propósito | ¿Por qué es necesario? |
|---------|-----------|------------------------|
| `git` | Control de versiones | Requerimiento de las prácticas profesionales |
| `unzip` | Descomprimir archivos | Composer lo necesita para instalar paquetes |
| `libzip-dev` | Librería de desarrollo para ZIP | Necesaria para compilar la extensión `zip` de PHP |
| `libpng-dev` | Librería de desarrollo para PNG | Necesaria para compilar la extensión `gd` de PHP |
| `libjpeg-dev` | Librería de desarrollo para JPEG | Necesaria para compilar la extensión `gd` de PHP |
| `libfreetype6-dev` | Librería de tipografías | Necesaria para `gd` (renderizado de texto en imágenes) |

### Limpieza de Caché

```dockerfile
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/*
```

**¿Por qué?**
- Reduce el tamaño final de la imagen Docker
- Elimina archivos temporales que no se necesitan
- Buena práctica en producción

---

## Extensiones de PHP

```dockerfile
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd
```

### Lista de Extensiones y Su Propósito

#### 1. **pdo** (PHP Data Objects)
- **Qué es**: Abstracción para acceso a bases de datos
- **Por qué**:
  - Interfaz consistente para múltiples bases de datos
  - Soporte nativo para prepared statements (previene SQL injection)
  - Mejor manejo de errores con excepciones
- **Ejemplo de uso**:
  ```php
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  ```

#### 2. **pdo_mysql**
- **Qué es**: Driver de MySQL para PDO
- **Por qué**:
  - Conecta PDO específicamente con MySQL
  - Requerimiento del proyecto (MySQL como base de datos)
  - Optimizado para MySQL 8.0

#### 3. **mysqli**
- **Qué es**: Extensión mejorada de MySQL
- **Por qué**:
  - Funciones específicas de MySQL que PDO no tiene
  - Útil como alternativa o complemento a PDO
  - Algunas librerías legacy la requieren

#### 4. **zip**
- **Qué es**: Manejo de archivos ZIP
- **Por qué**:
  - Composer la requiere para descomprimir paquetes
  - Útil si la API necesita generar/procesar archivos ZIP
  - Puede ser útil para backups o exportaciones

#### 5. **gd**
- **Qué es**: Librería de manipulación de imágenes
- **Por qué**:
  - Redimensionar imágenes (ej: portadas de libros, avatares)
  - Generar thumbnails
  - Crear gráficos dinámicos
  - Añadir marcas de agua

### Opciones de Compilación

```dockerfile
docker-php-ext-configure gd --with-freetype --with-jpeg
```
- **`--with-freetype`**: Habilita soporte para tipografías TrueType
- **`--with-jpeg`**: Habilita soporte para imágenes JPEG

```dockerfile
-j$(nproc)
```
- **`$(nproc)`**: Detecta el número de procesadores disponibles
- **`-j`**: Compila en paralelo (más rápido)
- Ejemplo: En un CPU de 4 cores, usa los 4 cores para compilar

---

## Instalación de Composer

```dockerfile
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

### Multi-Stage Build Pattern

**¿Qué hace esto?**
- Copia el binario de Composer desde la imagen oficial `composer:latest`
- NO instala todas las dependencias de la imagen de Composer

**Ventajas:**
- ✅ Imagen final más pequeña (solo ~2MB vs ~100MB)
- ✅ Solo el ejecutable de Composer, nada más
- ✅ Siempre la última versión estable
- ✅ No contamina el sistema con dependencias innecesarias

**Alternativa descartada:**
```dockerfile
# NO HACER ESTO (menos eficiente)
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
```

---

## Configuración de Apache

### Habilitar mod_rewrite

```dockerfile
RUN a2enmod rewrite
```

**¿Qué es mod_rewrite?**
- Módulo de Apache para reescribir URLs

**¿Por qué es necesario?**
- Permite URLs limpias: `/api/books/1` en lugar de `/index.php?route=books&id=1`
- Esencial para APIs REST modernas
- Permite usar `.htaccess` para routing
- Mejora SEO y experiencia de usuario

**Ejemplo de uso en .htaccess:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Configuración de DocumentRoot

```dockerfile
ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
```

**¿Qué hace?**
- Define la ruta raíz del servidor web
- Reemplaza dinámicamente la ruta en los archivos de configuración de Apache

**¿Por qué?**
- Flexibilidad para cambiar la ubicación del proyecto
- Si en el futuro quieres usar `/var/www/html/public`, solo cambias la variable
- Configuración centralizada y fácil de modificar

---

## Permisos y Seguridad

```dockerfile
RUN chown -R www-data:www-data /var/www/html
```

**¿Qué hace?**
- Cambia el propietario de todos los archivos a `www-data`
- `www-data` es el usuario con el que corre Apache

**¿Por qué?**
- Apache necesita leer los archivos PHP
- Si la aplicación escribe logs, necesita permisos de escritura
- Previene problemas de permisos en desarrollo y producción

**Seguridad:**
- Apache NO corre como root (más seguro)
- Si hay una vulnerabilidad, el atacante solo tiene permisos de `www-data`

---

## Configuración Final

```dockerfile
WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
```

### WORKDIR
- Establece el directorio de trabajo por defecto
- Todos los comandos siguientes se ejecutan aquí
- Cuando entras al contenedor, inicias en esta carpeta

### EXPOSE 80
- Documenta que el contenedor escucha en el puerto 80
- NO publica el puerto (eso lo hace docker-compose)
- Es metadata para otros desarrolladores

### CMD ["apache2-foreground"]
- Comando que se ejecuta al iniciar el contenedor
- `apache2-foreground`: Inicia Apache en primer plano (no como daemon)
- **Por qué foreground**: Docker necesita un proceso en primer plano para mantener el contenedor vivo

---

## Mejores Prácticas Aplicadas

✅ **Imagen base oficial**: Confiable y mantenida
✅ **Limpieza de caché**: Imagen más pequeña
✅ **Multi-stage build**: Solo lo necesario
✅ **Compilación paralela**: Builds más rápidos
✅ **Usuario no-root**: Mayor seguridad
✅ **Documentación con EXPOSE**: Clara para otros desarrolladores
✅ **Proceso foreground**: Contenedor estable

---

## Tamaño Final de la Imagen

Aproximadamente **450-500 MB**:
- Imagen base php:8.2-apache: ~400MB
- Extensiones compiladas: ~30MB
- Composer: ~2MB
- Dependencias del sistema: ~20MB

**Nota**: Aunque parece grande, incluye todo lo necesario para desarrollo y producción.
