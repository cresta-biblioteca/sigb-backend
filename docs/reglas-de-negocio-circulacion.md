# Reglas de negocio — Circulación

Cubre reservas, préstamos y renovaciones. Refleja el comportamiento real del código en `src/Circulacion/`.

---

## Reservas

### Cómo funciona

El lector reserva un artículo (libro, revista, etc.). El sistema detecta si hay ejemplares disponibles en ese momento:

- **Hay ejemplar disponible**: se asigna el ejemplar al instante y se calcula una fecha de vencimiento de **24 horas hábiles** (lunes a viernes). Si el lector no pasa a retirar en ese plazo, la reserva vence automáticamente.
- **No hay ejemplar disponible**: la reserva queda en **cola de espera** sin ejemplar asignado. Cuando otro lector devuelve un ejemplar y la reserva anterior vence, el sistema le asigna el ejemplar al primero de la cola y arranca el plazo de 24 horas hábiles desde ese momento.

### Cálculo de las 24 horas hábiles

Las horas de fin de semana no consumen el plazo.

| Reserva hecha el... | Vence el... |
|---|---|
| Lunes 15:00 | Martes 15:00 |
| Viernes 18:50 | Lunes 18:50 |
| Sábado o domingo | 24hs desde el lunes 00:00 |

### Estados posibles

| Estado | Descripción |
|---|---|
| `PENDIENTE` | Esperando que el lector retire (con o sin ejemplar asignado) |
| `COMPLETADA` | Se convirtió en préstamo |
| `CANCELADA` | El lector la canceló manualmente |
| `VENCIDA` | El plazo de retiro expiró sin que el lector pasara |

### Reglas para crear una reserva

1. El artículo debe existir.
2. El lector no puede tener una reserva `PENDIENTE` **ni** un préstamo activo para el mismo artículo. Esto evita que un lector acapare stock.

#### Casos válidos

```
// Lector sin reserva previa para el artículo → OK
POST /reservas { articulo_id: 5 }

// Lector que ya devolvió el préstamo anterior del mismo artículo → OK
POST /reservas { articulo_id: 5 }
```

#### Casos inválidos

```
// Lector que ya tiene reserva PENDIENTE del artículo → 409
POST /reservas { articulo_id: 5 }
// → "El lector ya tiene una reserva o préstamo activo para este artículo"

// Lector que tiene un préstamo activo del mismo artículo → 409
POST /reservas { articulo_id: 5 }
// → mismo error
```

### Reglas para cancelar una reserva

1. La reserva debe estar en estado `PENDIENTE`.
2. La reserva no debe estar vencida (aunque el estado todavía diga `PENDIENTE`, si el tiempo expiró ya no se puede cancelar).

#### Casos válidos

```
// Reserva PENDIENTE dentro del plazo → OK
PATCH /reservas/12/cancelar
```

#### Casos inválidos

```
// Reserva ya COMPLETADA → 422
PATCH /reservas/8/cancelar
// → "Solo reservas en estado PENDIENTE pueden ser canceladas"

// Reserva PENDIENTE pero con el plazo vencido → 422
PATCH /reservas/9/cancelar
// → "La reserva no puede ser cancelada porque ya venció el plazo"
```

---

## Préstamos

### Cómo funciona

Un préstamo **siempre nace de una reserva**. No se puede crear un préstamo sin una reserva previa. El bibliotecario convierte la reserva en préstamo cuando el lector pasa a retirar el material.

Al crear el préstamo:
- La reserva pasa a estado `COMPLETADA`.
- La fecha de vencimiento del préstamo es `hoy + duracion` (en días corridos, no hábiles).
- Se copia el límite de renovaciones del tipo de préstamo al préstamo (`max_renovaciones`).

### Estados posibles

| Estado | Descripción |
|---|---|
| `VIGENTE` | En curso, dentro del plazo |
| `COMPLETADO_EXITO` | Devuelto antes del vencimiento |
| `COMPLETADO_VENCIDO` | Devuelto después de la fecha de vencimiento |
| `INCONVENIENTE` | Devuelto con algún problema reportado (daño, pérdida, etc.) |

### Reglas para crear un préstamo

1. La reserva debe existir y estar en estado `PENDIENTE`.
2. La reserva no debe estar vencida.
3. La reserva debe tener un ejemplar asignado (no puede estar en cola de espera).
4. El ejemplar debe estar habilitado.
5. El tipo de préstamo debe existir y estar habilitado.
6. El lector no debe superar el `max_cantidad_prestamos` activos del mismo tipo de préstamo.

#### Casos válidos

```
// Reserva PENDIENTE con ejemplar asignado, tipo habilitado, lector dentro del límite → 201
POST /prestamos { reserva_id: 10, tipo_prestamo_id: 2 }
```

#### Casos inválidos

```
// Reserva en estado COMPLETADA o VENCIDA → 422
POST /prestamos { reserva_id: 10, tipo_prestamo_id: 2 }
// → "La reserva no está disponible para ser completada"

// Reserva sin ejemplar asignado (en cola) → 422
POST /prestamos { reserva_id: 11, tipo_prestamo_id: 2 }
// → "No hay ejemplar disponible"

// Tipo de préstamo deshabilitado → 422
POST /prestamos { reserva_id: 12, tipo_prestamo_id: 9 }
// → "El tipo de préstamo está deshabilitado"

// Lector con 30 préstamos activos del tipo "P30" (max = 30) → 422
POST /prestamos { reserva_id: 13, tipo_prestamo_id: 3 }
// → "Se superó el límite de 30 préstamos activos para este tipo"
```

---

## Renovaciones

### Cómo funciona

Renovar extiende la fecha de vencimiento de un préstamo activo. La nueva fecha se calcula como `hoy + dias_renovacion` (desde el momento en que se renueva, no desde el vencimiento original).

Se puede renovar con el mismo tipo de préstamo o cambiar a otro tipo distinto. Si se cambia el tipo, aplican los valores de `cant_dias_renovar` y `dias_renovacion` del nuevo tipo.

### Campos del tipo de préstamo que gobiernan las renovaciones

| Campo | Qué define |
|---|---|
| `renovaciones` | Cuántas veces se puede renovar el préstamo en total. `0` = no se puede renovar. |
| `cant_dias_renovar` | Cuántos días antes del vencimiento se abre la ventana de renovación. |
| `dias_renovacion` | Cuántos días dura la extensión al renovar. |

**Ejemplo**: tipo de préstamo de 30 días, `cant_dias_renovar = 5`, `dias_renovacion = 15`, `renovaciones = 2`.
- Se puede renovar solo en los últimos 5 días del préstamo.
- Cada renovación extiende 15 días desde el momento en que se renueva.
- Se puede renovar hasta 2 veces en total.

### Reglas para renovar

1. El préstamo debe estar en estado `VIGENTE` (no devuelto).
2. El préstamo no debe estar vencido (fecha de vencimiento ya pasó).
3. El tipo de préstamo debe permitir renovaciones (`renovaciones > 0`).
4. No se debe haber alcanzado el límite de renovaciones (`cant_renovaciones < max_renovaciones`).
5. Se debe estar dentro de la ventana de renovación: los días restantes hasta el vencimiento deben ser `<= cant_dias_renovar`.
6. No debe haber reservas pendientes de otros lectores para el mismo artículo.

### Casos válidos

```
// Préstamo vigente, faltan 3 días, cant_dias_renovar = 5, aún tiene renovaciones → OK
PATCH /prestamos/7/renovar

// Préstamo vigente, faltan 5 días (exactamente cant_dias_renovar = 5) → OK
PATCH /prestamos/7/renovar

// Cambiar a otro tipo de préstamo al renovar → OK
PATCH /prestamos/7/renovar { tipo_prestamo_id: 3 }
```

### Casos inválidos

```
// Préstamo ya devuelto → 422
PATCH /prestamos/7/renovar
// → "el préstamo ya fue devuelto"

// Fecha de vencimiento ya pasó → 422
PATCH /prestamos/7/renovar
// → "el préstamo está vencido"

// Tipo de préstamo con renovaciones = 0 → 422
PATCH /prestamos/7/renovar
// → "el tipo de préstamo no permite renovaciones"

// Se alcanzó el máximo (cant_renovaciones = max_renovaciones) → 422
PATCH /prestamos/7/renovar
// → "se alcanzó el límite de 2 renovaciones permitidas"

// Faltan 8 días y cant_dias_renovar = 5 (fuera de ventana) → 422
PATCH /prestamos/7/renovar
// → "solo se puede renovar dentro de los 5 días previos al vencimiento"

// Hay otro lector con reserva pendiente del mismo artículo → 422
PATCH /prestamos/7/renovar
// → "hay lectores con reservas pendientes para este artículo"
```

---

## Validaciones del tipo de préstamo

Al crear o actualizar un tipo de préstamo, se aplican estas reglas cruzadas además de las validaciones individuales de cada campo:

1. `cant_dias_renovar` debe ser estrictamente menor que `duracion`. No tiene sentido poder renovar desde antes de que el préstamo empiece.
2. Si `renovaciones = 0`, entonces `dias_renovacion` y `cant_dias_renovar` deben ser `0`. Configurarlos con otro valor no tiene efecto pero es inconsistente.

#### Ejemplos válidos

```json
// Préstamo de 30 días, renovable los últimos 5 días, la renovación dura 15 días, hasta 2 veces
{
  "codigo": "P30",
  "descripcion": "Préstamo mensual",
  "max_cantidad_prestamos": 5,
  "duracion": 30,
  "renovaciones": 2,
  "dias_renovacion": 15,
  "cant_dias_renovar": 5
}

// Préstamo de 7 días sin renovaciones
{
  "codigo": "P7",
  "descripcion": "Préstamo semanal",
  "max_cantidad_prestamos": 3,
  "duracion": 7,
  "renovaciones": 0,
  "dias_renovacion": 0,
  "cant_dias_renovar": 0
}
```

#### Ejemplos inválidos

```json
// cant_dias_renovar >= duracion → error
{
  "duracion": 5,
  "cant_dias_renovar": 5
}
// → "La cantidad de dias para renovar debe ser menor a la duracion del prestamo"

// renovaciones = 0 pero dias_renovacion > 0 → error
{
  "renovaciones": 0,
  "dias_renovacion": 15,
  "cant_dias_renovar": 0
}
// → "Los dias de renovacion deben ser 0 si no se permiten renovaciones"
```
