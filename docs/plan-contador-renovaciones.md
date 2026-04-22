# Plan: Contador de Renovaciones en Préstamos

## Contexto

El módulo de préstamos permite renovar un préstamo cambiando el tipo de préstamo en cada
renovación. El campo `renovaciones` de `TipoPrestamo` define el máximo de renovaciones
permitidas, pero actualmente no hay ningún contador en `Prestamo` que trackee cuántas se
realizaron. Esto permite renovar indefinidamente y también permite bypassear el límite
cambiando de tipo en cada renovación.

## Objetivo

- Limitar el total de renovaciones de un préstamo a un techo fijo, independientemente
  de los cambios de tipo que se hagan durante las renovaciones.
- El techo se fija en el momento de la creación del préstamo, tomando el valor de
  `renovaciones` del tipo de préstamo seleccionado inicialmente.
- Cambiar de tipo en una renovación **no** modifica el techo.

### Comportamiento esperado

```
Préstamo creado con tipo DOMICILIO (renovaciones = 3) → max_renovaciones = 3

  Renovación 1: cant_renovaciones = 1, cambia a tipo SALA  → permitido
  Renovación 2: cant_renovaciones = 2, vuelve a DOMICILIO  → permitido
  Renovación 3: cant_renovaciones = 3                      → permitido
  Renovación 4: 3 >= 3                                     → BLOQUEADO

El lector debe realizar una nueva reserva para volver a tener el artículo.
Si hay otro lector esperando, le corresponde el ejemplar.
Si no hay nadie esperando, puede reservar y empezar un ciclo nuevo.
```

### Edge case: tipo sin renovaciones

Si el tipo inicial tiene `renovaciones = 0`, el préstamo nace con `max_renovaciones = 0`
y no puede renovarse nunca. El mensaje de error debe distinguirlo del caso "límite alcanzado".

---

## Cambios requeridos

### 1. Migración de base de datos

Crear una nueva migración con:

```sql
ALTER TABLE prestamo
    ADD COLUMN cant_renovaciones INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN max_renovaciones  INT UNSIGNED NOT NULL DEFAULT 0;
```

`max_renovaciones` es un snapshot del valor de `TipoPrestamo::renovaciones` al momento de
crear el préstamo. Cambios posteriores al tipo no afectan préstamos activos.

---

### 2. Modelo `Prestamo`

Archivo: `src/Circulacion/Models/Prestamo.php`

**Agregar propiedades:**

```php
private int $cantRenovaciones = 0;
private int $maxRenovaciones  = 0;
```

**Actualizar `create()`** — agregar parámetro:

```php
public static function create(
    DateTimeImmutable $fechaPrestamo,
    DateTimeImmutable $fechaVencimiento,
    int $tipoPrestamoId,
    int $ejemplarId,
    int $lectorId,
    int $maxRenovaciones = 0,           // ← nuevo
    EstadoPrestamo $estado = EstadoPrestamo::VIGENTE
): self {
    // ...
    $prestamo->cantRenovaciones = 0;
    $prestamo->maxRenovaciones  = $maxRenovaciones;
}
```

**Actualizar `fromDatabase()`:**

```php
$prestamo->cantRenovaciones = (int) $row['cant_renovaciones'];
$prestamo->maxRenovaciones  = (int) $row['max_renovaciones'];
```

**Actualizar `renovar()`** — incrementar contador:

```php
public function renovar(DateTimeImmutable $nuevaFechaVencimiento): void
{
    $this->estado           = EstadoPrestamo::VIGENTE;
    $this->fechaVencimiento = $nuevaFechaVencimiento;
    $this->cantRenovaciones++;
}
```

**Agregar getters:**

```php
public function getCantRenovaciones(): int
{
    return $this->cantRenovaciones;
}

public function getMaxRenovaciones(): int
{
    return $this->maxRenovaciones;
}
```

**Actualizar `toArray()`:**

```php
'cant_renovaciones' => $this->cantRenovaciones,
'max_renovaciones'  => $this->maxRenovaciones,
```

---

### 3. `PrestamoService::createPrestamo()`

Archivo: `src/Circulacion/Services/PrestamoService.php`

Pasar `maxRenovaciones` al crear el préstamo:

```php
$prestamo = Prestamo::create(
    fechaPrestamo:    $fechaPrestamo,
    fechaVencimiento: $fechaVencimiento,
    tipoPrestamoId:   $tipoPrestamo->getId(),
    ejemplarId:       $ejemplarId,
    lectorId:         $reserva->getLectorId(),
    maxRenovaciones:  $tipoPrestamo->getRenovaciones(),  // ← nuevo
);
```

---

### 4. `PrestamoService::renovar()`

Archivo: `src/Circulacion/Services/PrestamoService.php`

**Nuevo orden de validaciones** (de menor a mayor costo de ejecución):

```php
// 1. Ya fue devuelto (sin query)
if ($prestamo->isDevuelto()) {
    throw new RenovacionNoPermitidaException('el préstamo ya fue devuelto');
}

// 2. Está vencido (sin query)
if ($prestamo->isVencido()) {
    throw new RenovacionNoPermitidaException('el préstamo está vencido');
}

// 3. Edge case: tipo inicial no permitía renovaciones (sin query)
if ($prestamo->getMaxRenovaciones() === 0) {
    throw new RenovacionNoPermitidaException('el tipo de préstamo no permite renovaciones');
}

// 4. Límite de renovaciones alcanzado (sin query)
if ($prestamo->getCantRenovaciones() >= $prestamo->getMaxRenovaciones()) {
    throw new RenovacionNoPermitidaException(
        "se alcanzó el límite de {$prestamo->getMaxRenovaciones()} renovaciones permitidas"
    );
}

// 5. Validar nuevo tipo si se cambia (1 query)
if ($tipoPrestamoId !== null) {
    $tipoPrestamo = $this->tipoPrestamoRepo->findById($tipoPrestamoId);
    if ($tipoPrestamo === null) {
        throw new TipoPrestamoNotFoundException();
    }
    if (!$tipoPrestamo->isHabilitado()) {
        throw new TipoPrestamoDeshabilitadoException();
    }
    $prestamo->setTipoPrestamo($tipoPrestamo);
} else {
    $tipoPrestamo = $this->tipoPrestamoRepo->findById($prestamo->getTipoPrestamoId());
    if ($tipoPrestamo === null) {
        throw new TipoPrestamoNotFoundException();
    }
    // Nota: el check renovaciones === 0 del tipo ya no es necesario,
    // está cubierto por el paso 3 con maxRenovaciones.
}

// 6. Ventana de renovación (sin query)
$diasParaVencimiento = (int) (new DateTimeImmutable())
    ->diff($prestamo->getFechaVencimiento())
    ->format('%r%a');

if ($diasParaVencimiento > $tipoPrestamo->getCantDiasRenovar()) {
    throw new RenovacionNoPermitidaException(
        "solo se puede renovar dentro de los {$tipoPrestamo->getCantDiasRenovar()} "
        . "días previos al vencimiento"
    );
}

// 7. Reservas pendientes para el artículo (1 query)
$ejemplar = $this->ejemplarRepo->findById($prestamo->getEjemplarId());
if ($ejemplar !== null) {
    if ($this->reservaRepo->existeReservaPendienteParaArticulo($ejemplar->getArticuloId())) {
        throw new RenovacionNoPermitidaException(
            'hay lectores con reservas pendientes para este artículo'
        );
    }
}

// 8. Renovar y persistir
$nuevaFechaVencimiento = (new DateTimeImmutable())->modify(
    "+{$tipoPrestamo->getDiasRenovacion()} days"
);

$prestamo->renovar($nuevaFechaVencimiento);
$this->prestamoRepo->updatePrestamo($prestamo);
```

**Eliminar** el check `$tipoPrestamo->getRenovaciones() === 0` que existía en la rama
`else` (reemplazado por el paso 3).

---

### 5. `PrestamoRepository`

Archivo: `src/Circulacion/Repositories/PrestamoRepository.php`

**`insertPrestamo()`** — agregar campos al INSERT:

```sql
INSERT INTO prestamo
    (fecha_prestamo, fecha_vencimiento, fecha_devolucion, estado,
     tipo_prestamo_id, ejemplar_id, lector_id, cant_renovaciones, max_renovaciones)
VALUES
    (:fecha_prestamo, :fecha_vencimiento, :fecha_devolucion, :estado,
     :tipo_prestamo_id, :ejemplar_id, :lector_id, :cant_renovaciones, :max_renovaciones)
```

```php
'cant_renovaciones' => $prestamo->getCantRenovaciones(),
'max_renovaciones'  => $prestamo->getMaxRenovaciones(),
```

**`updatePrestamo()`** — agregar `cant_renovaciones` al UPDATE:

```sql
UPDATE prestamo
SET estado             = :estado,
    fecha_vencimiento  = :fecha_vencimiento,
    fecha_devolucion   = :fecha_devolucion,
    tipo_prestamo_id   = :tipo_prestamo_id,
    cant_renovaciones  = :cant_renovaciones
WHERE id = :id
```

```php
'cant_renovaciones' => $prestamo->getCantRenovaciones(),
```

> `max_renovaciones` no se incluye en el UPDATE porque nunca cambia después de la creación.

---

### 6. `PrestamoResponse`

Archivo: `src/Circulacion/Dtos/Response/PrestamoResponse.php`

Agregar al constructor y a `jsonSerialize()`:

```php
private int $cantRenovaciones,
private int $maxRenovaciones,
```

```php
'cant_renovaciones' => $this->cantRenovaciones,
'max_renovaciones'  => $this->maxRenovaciones,
```

Actualizar `PrestamoMapper::toResponse()` para mapear los nuevos campos desde el modelo.

---

### 7. Tests

Archivo: `tests/Unit/Circulacion/PrestamoServiceTest.php`

**Casos nuevos a agregar:**

- `renovar falla si el tipo inicial no permitia renovaciones` (`maxRenovaciones = 0`)
- `renovar falla si se alcanzo el limite de renovaciones` (`cantRenovaciones >= maxRenovaciones`)

**Casos existentes a actualizar:**

- Happy path de `createPrestamo`: incluir `maxRenovaciones` en la verificación del objeto
  creado.
- Happy path de `renovar`: verificar que `cantRenovaciones` se incrementa en la respuesta.
- Actualizar mocks de `Prestamo` que usen `makePartial()` para inicializar los nuevos campos.

---

## Resumen de archivos a modificar

| Archivo | Tipo de cambio |
|---|---|
| `db/migrations/YYYYMMDD_add_renovaciones_to_prestamo.php` | Nuevo |
| `src/Circulacion/Models/Prestamo.php` | Modificar |
| `src/Circulacion/Services/PrestamoService.php` | Modificar |
| `src/Circulacion/Repositories/PrestamoRepository.php` | Modificar |
| `src/Circulacion/Dtos/Response/PrestamoResponse.php` | Modificar |
| `src/Circulacion/Mappers/PrestamoMapper.php` | Modificar |
| `tests/Unit/Circulacion/PrestamoServiceTest.php` | Modificar |
