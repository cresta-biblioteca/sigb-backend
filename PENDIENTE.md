# Bugs pendientes — Flujo JWT

Detectados durante el análisis del flujo de autenticación (2026-02-19).

---

## Bug 1 — CRÍTICO: `getenv()` no lee variables de phpdotenv

**Archivo:** `src/Shared/Security/JwtTokenProvider.php:22`

`Dotenv::createImmutable()` popula `$_ENV` pero no llama a `putenv()`, por lo que
`getenv('JWT_SECRET')` siempre retorna `false`. El constructor lanza una `Exception`
en cualquier request, incluyendo las públicas.

**Fix:** reemplazar `getenv('JWT_SECRET')` por `$_ENV['JWT_SECRET'] ?? null`.

---

## Bug 2 — CRÍTICO: `UserLoginResponse` se serializa como `{}`

**Archivo:** `src/Auth/Dtos/Response/UserLoginResponse.php:9`

La propiedad `$token` es `private`. `json_encode()` solo serializa propiedades públicas,
por lo que la respuesta del login es `{}` en lugar de `{"token": "..."}`.

**Fix:** cambiar `private string $token` a `public`, o implementar `JsonSerializable`.

---

## Bug 3 — CRÍTICO: `AuthRepository::create()` siempre retorna `null`

**Archivo:** `src/Auth/Repositories/AuthRepository.php:34`

Tras ejecutar un `INSERT`, se llama a `$stmt->fetch()` que siempre retorna `false`
porque los INSERT no producen resultset. Esto hace que `create()` devuelva `null`,
y en `AuthService::register()` la línea `$savedUser->setRole($role)` tira un error fatal.

**Fix:** usar `$this->pdo->lastInsertId()` para obtener el ID y luego buscar el registro
con un SELECT.

---

## Bug 4 — MENOR: `RoleNotFoundException` sin catch en el controller

**Archivo:** `src/Auth/Controllers/AuthController.php:76`

Si el rol del usuario no existe en la base de datos, `AuthService::login()` lanza
`RoleNotFoundException`. El controller no la captura, cae en el bloque `\Throwable`
genérico y retorna 500.

**Fix:** agregar `catch (RoleNotFoundException $e)` con una respuesta apropiada (500
con mensaje descriptivo, o 401 si se prefiere no exponer el detalle).

---

## Bug 5 — DISEÑO: hashing de password inconsistente

**Archivos:**
- `src/Auth/Models/User.php:87` — usa `password_hash($password, PASSWORD_DEFAULT)`
- `src/Shared/Security/PasswordEncoder.php` — usa `PASSWORD_BCRYPT` con cost 12

`PasswordEncoder` se inyecta en `AuthService` pero nunca se usa para hashear: el hash
se genera dentro de `User::setPassword()`. El encoder solo se usa para verificar.

**Fix:** mover la responsabilidad del hashing completamente a `PasswordEncoder`.
`User::setPassword()` debería recibir el hash ya procesado, y `AuthService::register()`
debería llamar a `$passwordEncoder->hash($password)` antes de pasarlo al modelo.

---

## Bug 6 — MENOR: `JWT_SECRET` faltante en `.env.example`

**Archivo:** `.env.example`

La variable `JWT_SECRET` no está documentada en `.env.example`. Cualquier desarrollador
que clone el proyecto no sabrá que debe agregarla (debe tener al menos 32 caracteres).

**Fix:** agregar `JWT_SECRET=cambia_esto_por_un_secreto_seguro_de_32_chars` al `.env.example`.
