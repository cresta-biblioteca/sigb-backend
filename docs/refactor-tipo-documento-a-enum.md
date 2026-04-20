# Refactor: reemplazar `tipo_documento` por enum en `articulo`

## Contexto y motivación

La entidad `TipoDocumento` fue creada como una lookup table con CRUD completo
(5 endpoints REST, controller, service, repository, DTOs, validators, mapper,
tests). En la práctica esto genera complejidad innecesaria porque:

- Los tipos de documento son conocidos de antemano y estables (estándar MARC21).
- Nunca se contempló que el staff administre tipos desde el panel.
- El campo `renovable` en `tipo_documento` está en el lugar equivocado: la
  posibilidad de renovar un préstamo la determina la disponibilidad de
  ejemplares, no el tipo de documento.
- Una foreign key a una tabla de 4-5 filas no aporta valor frente a una columna
  `tipo` con validación en la capa de aplicación.

## Decisión

Reemplazar la tabla `tipo_documento` y toda su infraestructura por:
- Una columna `tipo VARCHAR(30) NOT NULL` en la tabla `articulo`.
- Un PHP Enum `TipoArticulo` con los valores definidos según MARC21.

## Valores del enum (referencia MARC21 Leader/06)

| Valor PHP | DB value | MARC21 | Descripción |
|---|---|---|---|
| `LIBRO` | `libro` | `a/m` | Monografía |
| `REVISTA` | `revista` | `a/s` | Publicación seriada |
| `TESIS` | `tesis` | `a/m` | Tesis / trabajo académico |
| `MAPA` | `mapa` | `e` | Material cartográfico |
| `PARTITURA` | `partitura` | `c` | Música notada |

Los valores `grabacion_sonora`, `video`, `recurso_electronico` y
`material_grafico` (MARC21 `i/j`, `g`, `m`, `k`) quedan fuera del alcance
inicial. Se agregan cuando el contexto lo requiera.

---

## Alcance del cambio

### Archivos a eliminar

```
src/Catalogo/Articulos/Models/TipoDocumento.php
src/Catalogo/Articulos/Controllers/TipoDocumentoController.php
src/Catalogo/Articulos/Services/TipoDocumentoService.php
src/Catalogo/Articulos/Repository/TipoDocumentoRepository.php
src/Catalogo/Articulos/Dtos/Response/TipoDocumentoResponse.php
src/Catalogo/Articulos/Dtos/Request/CreateTipoDocumentoRequest.php
src/Catalogo/Articulos/Dtos/Request/UpdateTipoDocumentoRequest.php
src/Catalogo/Articulos/Mappers/TipoDocumentoMapper.php
src/Catalogo/Articulos/Validators/TipoDocumentoRequestValidator.php
src/Catalogo/Articulos/Exceptions/TipoDocumentoAlreadyExistsException.php
src/Catalogo/Articulos/Exceptions/TipoDocumentoNotFoundException.php
routes/tipoDocumento.php
tests/Unit/Catalogo/Articulos/TipoDocumento/TipoDocumentoServiceTest.php
```

### Archivos a crear

```
src/Shared/Enums/TipoArticulo.php   ← PHP Enum backed string
```

### Archivos a modificar

| Archivo | Qué cambia |
|---|---|
| `src/Catalogo/Articulos/Models/Articulo.php` | Reemplazar `$tipoDocumentoId` + `$tipoDocumento` por `$tipo: string` |
| `src/Catalogo/Articulos/Repository/ArticuloRepository.php` | Cambiar `tipo_documento_id` → `tipo` en INSERT, UPDATE y SELECT |
| `src/Catalogo/Articulos/Services/ArticuloService.php` | Adaptar business rule (ver sección Business Rules) |
| `src/Catalogo/Articulos/Mappers/ArticuloMapper.php` | Usar `tipo` en lugar de `tipoDocumentoId` / `tipoDocumento` anidado |
| `src/Catalogo/Libros/Mappers/LibroMapper.php` | Reemplazar `tipoDocumentoId` por `tipo` |
| `src/Catalogo/Articulos/Validators/ArticuloRequestValidator.php` | Validar que `tipo` sea uno de los valores del enum |
| `routes/api.php` o archivo principal de rutas | Quitar `require/include` de `routes/tipoDocumento.php` |
| `db/seeds/CatalogoTestDataSeeder.php` | Quitar inserción en `tipo_documento`; usar `tipo = 'libro'` en articulo |
| `tests/Unit/Catalogo/Articulos/ArticuloServiceTest.php` | Actualizar fixtures y asserts que usen `tipoDocumentoId` |

---

## Pasos de implementación

### 1. Crear el PHP Enum

```php
// src/Shared/Enums/TipoArticulo.php
namespace App\Shared\Enums;

enum TipoArticulo: string
{
    case LIBRO     = 'libro';
    case REVISTA   = 'revista';
    case TESIS     = 'tesis';
    case MAPA      = 'mapa';
    case PARTITURA = 'partitura';
}
```

### 2. Escribir la migración Phinx

La migración debe ejecutarse en este orden para respetar las constraints de FK:

```php
// Paso 1: agregar la nueva columna
$this->table('articulo')
    ->addColumn('tipo', 'string', ['limit' => 30, 'null' => false, 'default' => 'libro'])
    ->update();

// Paso 2: poblar `tipo` a partir de tipo_documento existente (migración de datos)
// Mapear codigo → valor enum. Ajustar según los datos reales en producción/staging.
$this->execute("
    UPDATE articulo a
    JOIN tipo_documento td ON a.tipo_documento_id = td.id
    SET a.tipo = LOWER(td.descripcion)
");

// Paso 3: quitar el default una vez poblados los datos
$this->table('articulo')
    ->changeColumn('tipo', 'string', ['limit' => 30, 'null' => false, 'default' => null])
    ->update();

// Paso 4: eliminar FK e índice
$this->table('articulo')
    ->dropForeignKey('tipo_documento_id')
    ->removeIndex(['tipo_documento_id'])
    ->removeColumn('tipo_documento_id')
    ->update();

// Paso 5: drop tabla tipo_documento
$this->table('tipo_documento')->drop()->update();

// Paso 6: índice en la nueva columna (útil para filtros por tipo)
$this->table('articulo')
    ->addIndex(['tipo'])
    ->update();
```

> **Atención**: el paso 2 (migración de datos) depende de los valores reales en
> la columna `descripcion` de `tipo_documento`. Verificar contra el ambiente de
> staging antes de correr en producción.

### 3. Actualizar el modelo `Articulo`

- Quitar `private int $tipoDocumentoId` y `private ?TipoDocumento $tipoDocumento`.
- Agregar `private string $tipo`.
- Actualizar `create()`, `fromDatabase()`, getters/setters y `toArray()`.

### 4. Actualizar `ArticuloRepository`

- En INSERT: reemplazar `tipo_documento_id => $articulo->getTipoDocumentoId()` por `tipo => $articulo->getTipo()`.
- En UPDATE: ídem.
- En SELECT / `fromDatabase()`: leer columna `tipo`.
- Quitar cualquier JOIN con `tipo_documento`.

### 5. Adaptar la business rule en `ArticuloService`

La restricción actual impide cambiar `tipo_documento_id` si el artículo ya tiene
un `Libro` asociado. **La regla se mantiene**, solo cambia la propiedad evaluada:

```php
// Antes
if ($request->getTipoDocumentoId() !== null
    && $request->getTipoDocumentoId() !== $articulo->getTipoDocumentoId()
    && $this->libroRepository->existsByArticuloId($articulo->getId())) {
    throw new BusinessRuleException('No se puede cambiar el tipo de documento...');
}

// Después
if ($request->getTipo() !== null
    && $request->getTipo() !== $articulo->getTipo()
    && $this->libroRepository->existsByArticuloId($articulo->getId())) {
    throw new BusinessRuleException('No se puede cambiar el tipo de artículo...', 'tipo');
}
```

### 6. Actualizar mappers

- `ArticuloMapper`: exponer `tipo` como string plano en la response. Quitar el
  objeto `tipoDocumento` anidado.
- `LibroMapper`: reemplazar `tipoDocumentoId` por `tipo`.

### 7. Actualizar validación en `ArticuloRequestValidator`

```php
// Validar que el valor sea uno de los casos del enum
$tiposValidos = array_column(TipoArticulo::cases(), 'value');
if (!in_array($input['tipo'], $tiposValidos, true)) {
    // lanzar ValidationException
}
```

### 8. Actualizar rutas

Quitar el `require`/`include` de `routes/tipoDocumento.php` del archivo
principal de rutas.

### 9. Actualizar el seeder

Quitar la inserción en `tipo_documento`. En la inserción de `articulo` usar
directamente `'tipo' => 'libro'`.

### 10. Eliminar archivos de TipoDocumento

Borrar todos los archivos listados en la sección "Archivos a eliminar".

### 11. Actualizar y correr tests

- Eliminar `TipoDocumentoServiceTest.php`.
- Actualizar fixtures en `ArticuloServiceTest` (reemplazar `tipoDocumentoId`
  por `tipo`).
- Correr la suite completa: `docker-compose exec web composer test`.

---

## Business rules que se mantienen

| Regla | Dónde vive |
|---|---|
| `tipo` debe ser uno de los valores del enum | `ArticuloRequestValidator` |
| No se puede cambiar `tipo` si el artículo ya tiene un `Libro` asociado | `ArticuloService` |

## Business rules que desaparecen

| Regla eliminada | Motivo |
|---|---|
| `renovable` por tipo de documento | La renovabilidad la determina la disponibilidad de ejemplares, no el tipo |

---

## Impacto en la API pública

| Endpoint | Cambio |
|---|---|
| `GET /articulos` / `GET /articulos/{id}` | Response: se quita objeto `tipoDocumento` anidado, se agrega campo plano `tipo: "libro"` |
| `POST /articulos` / `PUT /articulos/{id}` | Body: `tipoDocumentoId` → `tipo` (string) |
| `GET /libros` / `GET /libros/{id}` | Response: `tipoDocumentoId` → `tipo` |
| `GET /documentos` y variantes | **Eliminados** |

> Coordinar con el equipo de frontend el cambio de contrato antes de hacer merge.

---

## Checklist para el PR

- [ ] Migración Phinx escrita y probada en local
- [ ] Enum `TipoArticulo` creado
- [ ] Modelo `Articulo` actualizado
- [ ] Repository actualizado (sin referencias a `tipo_documento_id`)
- [ ] Mappers actualizados
- [ ] Validador actualizado
- [ ] Business rule adaptada en `ArticuloService`
- [ ] Rutas: include de `tipoDocumento.php` eliminado
- [ ] Seeder actualizado
- [ ] Archivos de TipoDocumento eliminados
- [ ] Tests actualizados y pasando (`composer test`)
- [ ] PSR-12 validado (`composer cs`)
- [ ] Frontend notificado del cambio de contrato de la API
