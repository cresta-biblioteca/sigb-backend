# Explicación de Dependencias de Composer

Este documento explica todas las dependencias definidas en `composer.json` y las razones técnicas para incluirlas.

---

## Estructura General del composer.json

```json
{
  "name": "sigb/api-cresta",
  "description": "Sistema de Gestión Bibliotecaria - API",
  "type": "project",
  "require": { ... },
  "require-dev": { ... },
  "autoload": { ... },
  "autoload-dev": { ... },
  "scripts": { ... },
  "config": { ... }
}
```

---

## Dependencias de Producción (require)

Estas dependencias son **necesarias** para que la aplicación funcione en producción.

```json
"require": {
    "php": ">=8.2",
    "ext-pdo": "*",
    "ext-mysqli": "*",
    "ext-json": "*"
}
```

### 1. `"php": ">=8.2"`

**¿Qué es?**
- Especifica la versión mínima de PHP requerida

**¿Por qué PHP 8.2?**
- ✅ **Rendimiento**: Hasta 30% más rápido que PHP 7.4
- ✅ **Características modernas**:
  - Readonly classes
  - Null, false, and true como tipos standalone
  - Mejor manejo de errores
- ✅ **Soporte oficial**: Actualizaciones de seguridad hasta noviembre 2025
- ✅ **Compatibilidad**: La mayoría de librerías soportan 8.2

**Sintaxis `>=8.2`:**
- Acepta PHP 8.2, 8.3, 8.4, etc.
- Rechaza PHP 8.1 o anteriores

### 2. `"ext-pdo": "*"`

**¿Qué es?**
- **PDO** = PHP Data Objects
- Capa de abstracción para acceso a bases de datos

**¿Por qué es necesario?**
- Conexión segura a MySQL
- Prepared statements (previene SQL injection)
- Manejo consistente de errores
- Código portable entre diferentes bases de datos

**Ejemplo de uso:**
```php
$pdo = new PDO($dsn, $user, $password);
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();
```

**Ventajas sobre consultas directas:**
```php
// ❌ INSEGURO (vulnerable a SQL injection)
$result = mysqli_query($conn, "SELECT * FROM books WHERE id = $bookId");

// ✅ SEGURO (prepared statement)
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
```

**Sintaxis `"*"`:**
- Acepta cualquier versión de la extensión
- Solo verifica que esté instalada

### 3. `"ext-mysqli": "*"`

**¿Qué es?**
- **MySQLi** = MySQL Improved Extension
- Interfaz específica para MySQL

**¿Por qué incluirlo si ya tenemos PDO?**
- Funciones específicas de MySQL que PDO no tiene
- Backup/alternativa a PDO
- Algunas librerías legacy lo requieren
- Mejor rendimiento en operaciones MySQL específicas

**Cuándo usar cada uno:**
- **PDO**: Para la mayoría de operaciones (CRUD básico)
- **MySQLi**: Para características avanzadas de MySQL

### 4. `"ext-json": "*"`

**¿Qué es?**
- Extensión para trabajar con JSON

**¿Por qué es necesario?**
- Tu API devuelve respuestas en JSON
- Parsing de JSON en requests
- Configuraciones en formato JSON

**Funciones clave:**
```php
json_encode($data);  // Array/Object → JSON string
json_decode($json);  // JSON string → Array/Object
```

**Nota**: Aunque viene por defecto en PHP, es buena práctica declararlo explícitamente.

---

## Dependencias de Desarrollo (require-dev)

Estas dependencias solo se instalan en **entorno de desarrollo**, NO en producción.

```json
"require-dev": {
    "pestphp/pest": "^2.34",
    "phpunit/phpunit": "^10.5"
}
```

### 1. `"pestphp/pest": "^2.34"`

**¿Qué es Pest?**
- Framework de testing moderno para PHP
- Construido sobre PHPUnit
- Sintaxis más limpia y expresiva

**¿Por qué Pest?**
- ✅ **Menos código**: Tests más concisos
- ✅ **Legibilidad**: Sintaxis natural
- ✅ **Rápido de escribir**: Menos boilerplate
- ✅ **Cumple requerimiento**: Testing con PHPUnit/Pest

**Comparación de sintaxis:**

```php
// ❌ PHPUnit tradicional (verbose)
class ExampleTest extends TestCase
{
    public function test_suma()
    {
        $result = 2 + 2;
        $this->assertEquals(4, $result);
    }
}

// ✅ Pest (conciso y legible)
test('suma correctamente', function () {
    $result = 2 + 2;
    expect($result)->toBe(4);
});
```

**Características de Pest:**
- Expectations: `expect($value)->toBe(4)`
- Higher-order tests
- Parallel execution
- Cobertura de código
- Snapshots

**Sintaxis `^2.34`:**
- `^` = Compatible con versiones menores
- Acepta: 2.34, 2.35, 2.99
- Rechaza: 3.0 (puede tener breaking changes)

### 2. `"phpunit/phpunit": "^10.5"`

**¿Qué es PHPUnit?**
- Framework de testing estándar de PHP
- Motor que usa Pest internamente

**¿Por qué incluirlo si usamos Pest?**
- Pest depende de PHPUnit
- Puedes usar PHPUnit directamente si lo prefieres
- Algunas funcionalidades solo están en PHPUnit
- Integración con IDEs (PhpStorm reconoce PHPUnit)

**Versión 10.5:**
- Versión estable y moderna
- Compatible con PHP 8.2
- Mejoras de rendimiento

**Puedes usar ambos:**
```php
// test con Pest
test('ejemplo', fn() => expect(true)->toBeTrue());

// test con PHPUnit
class DatabaseTest extends TestCase {
    public function test_connection() { ... }
}
```

---

## Autoloading (PSR-4)

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
},
"autoload-dev": {
    "psr-4": {
        "Tests\\": "tests/"
    }
}
```

### ¿Qué es PSR-4?

**PSR** = PHP Standard Recommendation
**PSR-4** = Estándar de autoloading de clases

### ¿Cómo funciona?

**Mapeo namespace → directorio:**
```
App\Database\Connection  →  src/Database/Connection.php
App\Models\Book          →  src/Models/Book.php
Tests\Unit\BookTest      →  tests/Unit/BookTest.php
```

### ¿Por qué es importante?

**Sin autoloading (manual):**
```php
require_once 'src/Database/Connection.php';
require_once 'src/Models/Book.php';
require_once 'src/Controllers/BookController.php';
// ... muchos más requires
```

**Con autoloading PSR-4:**
```php
require_once 'vendor/autoload.php';
// Listo, todas las clases se cargan automáticamente
```

### Ventajas:
- ✅ No más `require` o `include` manual
- ✅ Clases se cargan solo cuando se necesitan (lazy loading)
- ✅ Estándar de la industria
- ✅ Compatible con todas las librerías modernas

---

## Scripts de Composer

```json
"scripts": {
    "test": "pest",
    "test:unit": "pest --filter=Unit",
    "test:integration": "pest --filter=Integration",
    "test:coverage": "pest --coverage"
}
```

### ¿Qué son los scripts?

Atajos para comandos que usas frecuentemente.

### Scripts Definidos:

#### 1. `composer test`
```bash
# Equivale a:
./vendor/bin/pest
```
- Ejecuta **todos** los tests
- Unit + Integration

#### 2. `composer test:unit`
```bash
# Equivale a:
./vendor/bin/pest --filter=Unit
```
- Solo tests en `tests/Unit/`
- Tests rápidos sin base de datos

#### 3. `composer test:integration`
```bash
# Equivale a:
./vendor/bin/pest --filter=Integration
```
- Solo tests en `tests/Integration/`
- Tests que usan base de datos, APIs, etc.

#### 4. `composer test:coverage`
```bash
# Equivale a:
./vendor/bin/pest --coverage
```
- Genera reporte de cobertura de código
- Muestra qué porcentaje del código está testeado

### Ventajas:
- ✅ No necesitas recordar rutas de binarios
- ✅ Comandos consistentes en cualquier proyecto
- ✅ Fácil de documentar en el README
- ✅ CI/CD puede usar estos scripts

---

## Configuración (config)

```json
"config": {
    "allow-plugins": {
        "pestphp/pest-plugin": true
    },
    "optimize-autoloader": true,
    "preferred-install": "dist",
    "sort-packages": true
}
```

### `allow-plugins`
- **Qué hace**: Permite que Pest instale su plugin
- **Por qué**: Seguridad, Composer pregunta antes de ejecutar plugins

### `optimize-autoloader: true`
- **Qué hace**: Genera un mapa de clases optimizado
- **Ventaja**: Carga de clases más rápida
- **Cuándo**: Se aplica en `composer install --optimize-autoloader`

### `preferred-install: "dist"`
- **Qué hace**: Descarga paquetes en formato `.zip`
- **Alternativa**: `"source"` clona repositorios git
- **Ventaja**: Instalación más rápida, menos espacio

### `sort-packages: true`
- **Qué hace**: Ordena alfabéticamente las dependencias
- **Ventaja**: Diffs más limpios en git

---

## Estabilidad

```json
"minimum-stability": "stable",
"prefer-stable": true
```

### `minimum-stability: "stable"`
- Solo instala versiones estables (no beta, alpha, dev)
- Previene instalar código inestable accidentalmente

### `prefer-stable: true`
- Si hay versión estable disponible, elige esa
- Incluso si una dependencia permite versiones inestables

---

## Instalación de Dependencias

### Producción:
```bash
composer install --no-dev --optimize-autoloader
```
- `--no-dev`: No instala Pest/PHPUnit
- `--optimize-autoloader`: Autoloader más rápido

### Desarrollo:
```bash
composer install
```
- Instala todas las dependencias (require + require-dev)

---

## Añadir Nuevas Dependencias

### Para producción:
```bash
composer require nombre/paquete
```

### Para desarrollo:
```bash
composer require --dev nombre/paquete
```

### Ejemplos comunes:

```bash
# Librería de validación
composer require respect/validation

# Librería de routing
composer require nikic/fast-route

# ORM (base de datos)
composer require illuminate/database

# Testing adicional
composer require --dev fakerphp/faker
```

---

## Resumen

| Dependencia | Tipo | Propósito |
|-------------|------|-----------|
| php >=8.2 | Runtime | Versión mínima de PHP |
| ext-pdo | Runtime | Conexión segura a MySQL |
| ext-mysqli | Runtime | Funciones específicas MySQL |
| ext-json | Runtime | API REST (JSON) |
| pestphp/pest | Dev | Testing moderno |
| phpunit/phpunit | Dev | Motor de testing |

**Todas cumplen con los requerimientos de las prácticas profesionales.**
