# Protección de rutas por rol

## Contexto

El sistema usa `bramus/router` que **solo acepta un callable por ruta** — no soporta chaining de middlewares nativamente. La autenticación JWT ya está resuelta globalmente en `public/index.php` mediante `$router->before(...)`.

La autorización por rol se implementa con una función helper `withRole()` que envuelve el handler de cada ruta.

## Capas de seguridad

```
Petición entrante
  → [Capa 1] CorsMiddleware         (index.php)
  → [Capa 2] JwtMiddleware          ($router->before en index.php)
      ✗ token inválido/ausente → 401 Unauthorized
      ✓ setea $_SERVER['USER_ID'], ['USER_ROLE'], ['USER_DNI']
  → [Capa 3] withRole([...])        (closure en cada ruta)
      ✗ rol no permitido → 403 Forbidden
      ✓ ejecuta el controller
```

Rutas públicas (excluidas del JWT guard): `auth/login`, `auth/register`, `docs/`

## Roles disponibles

| Rol          | Descripción                        |
|--------------|------------------------------------|
| `admin`      | Acceso total                       |
| `catalogador`| Gestión del catálogo bibliográfico |
| `lector`     | Consultas y operaciones propias    |

## Implementación

### 1. Definir `withRole()` en `public/index.php`

Agregar la función antes de los `require_once` de rutas:

```php
function withRole(array $roles, callable $handler): callable
{
    return function () use ($roles, $handler) {
        if (!in_array($_SERVER['USER_ROLE'] ?? '', $roles, true)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden.']);
            exit();
        }
        $handler(...func_get_args());
    };
}
```

### 2. Usar `withRole()` en los archivos de rutas

```php
// Solo admin
$router->get('/usuarios', withRole(['admin'], function () use ($controller) {
    $controller->getAll();
}));

// Admin o catalogador
$router->post('/libros', withRole(['admin', 'catalogador'], function () use ($controller) {
    $controller->create();
}));

// Cualquier rol autenticado (lector, admin, catalogador)
$router->get('/libros', withRole(['admin', 'catalogador', 'lector'], function () use ($controller) {
    $controller->getAll();
}));

// Ruta con parámetro
$router->get('/reservas/{id}', withRole(['admin', 'lector'], function ($id) use ($controller) {
    $controller->getById((int) $id);
}));
```

### 3. Rutas sin restricción de rol (solo autenticación JWT)

Si una ruta solo requiere estar autenticado (cualquier rol), no usar `withRole()`:

```php
$router->get('/perfil', function () use ($controller) {
    $controller->getMiPerfil();
});
```

## Dónde vive cada archivo relevante

| Archivo                                      | Responsabilidad                            |
|----------------------------------------------|--------------------------------------------|
| `public/index.php`                           | JWT guard global + definición de withRole()|
| `src/Shared/Middlewares/JwtMiddleware.php`   | Valida token, setea USER_ROLE en $_SERVER  |
| `src/Shared/Security/JwtTokenProvider.php`   | Genera y valida JWT (payload: sub, role, dni) |
| `routes/*.php`                               | Definición de rutas con withRole()         |
