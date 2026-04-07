# ADR-003: Cron Job para Expiración de Reservas Vencidas

## Estado

Aceptada

## Fecha

2026-04-07

## Contexto

Cuando un lector realiza una reserva y hay ejemplar disponible, el sistema le asigna ese ejemplar y fija una **fecha de vencimiento** (calculada por `HorarioBiblioteca::calcularVencimientoReserva`). Si el lector no retira el libro antes de esa fecha, la reserva queda en estado `PENDIENTE` con fecha vencida indefinidamente, bloqueando el ejemplar para otros lectores en cola.

El sistema necesita un mecanismo que periódicamente:

1. Detecte reservas `PENDIENTE` cuya `fecha_vencimiento` ya expiró.
2. Las marque como `VENCIDA`.
3. Libere el ejemplar y lo asigne a la siguiente reserva en cola de espera (si existe).

Se evaluaron las siguientes alternativas:

1. **Lazy expiration**: marcar la reserva como vencida en el momento en que otro proceso la lea (ej: al intentar hacer una nueva reserva).
2. **Cron job**: proceso externo que corre en background y persiste el estado periódicamente.
3. **Event-driven**: emitir un evento al crear la reserva y procesarlo con un scheduler interno.

## Decisión

Se implementó un **cron job como servicio Docker independiente** que ejecuta un script PHP CLI cada 30 minutos.

### Arquitectura

```
docker-compose
├── web   (PHP + Apache)       ← HTTP requests
├── db    (MySQL)
└── cron  (mismo Dockerfile)   ← corre `cron -f` en foreground
         └── /etc/cron.d/sigb-crontab
                  └── cada 30 min → php bin/expire-reservas.php
```

### Archivos involucrados

| Archivo | Rol |
|---------|-----|
| `docker/cron/sigb-crontab` | Definición del cron en formato system crontab (`/etc/cron.d/`) |
| `Dockerfile` | Instala `cron`, copia y aplica permisos al crontab |
| `docker-compose.yml` | Servicio `cron` con `command: ["cron", "-f"]` |
| `bin/expire-reservas.php` | Script PHP CLI: bootstrap + invocación del servicio |
| `ReservaService::expirarReservasVencidas()` | Lógica de negocio de expiración |
| `ReservaRepository` | Queries: `getVencidasPendientes()`, `getProximaEnCola()`, `update()` |

### Formato del crontab (`docker/cron/sigb-crontab`)

```
*/30 * * * * root php /var/www/html/bin/expire-reservas.php >> /var/log/sigb-cron.log 2>&1
```

> **Importante**: el formato es system crontab (`/etc/cron.d/`), no el de usuario (`crontab -e`).
> Requiere el campo `usuario` (`root`) entre la expresión y el comando.
> El archivo **debe terminar con una línea en blanco** para que cron lo reconozca.

### Flujo de expiración (`expirarReservasVencidas`)

```
getVencidasPendientes()
    → reservas con estado = PENDIENTE
             AND fecha_vencimiento IS NOT NULL
             AND fecha_vencimiento < NOW()

para cada reserva vencida:
    BEGIN TRANSACTION
        reserva.marcarVencida()           → estado = VENCIDA
        reservaRepository.update(reserva)

        getProximaEnCola(articuloId)
            → siguiente reserva PENDIENTE
                       AND ejemplar_id IS NULL   ← reservas en cola de espera
                       ORDER BY created_at ASC

        si existe próxima en cola:
            proximaEnCola.setEjemplarId(...)
            proximaEnCola.setFechaVencimiento(calcularVencimientoReserva(now))
            reservaRepository.update(proximaEnCola)
    COMMIT
    (si falla → ROLLBACK + re-throw para logging en CLI)
```

### Transacciones

La transacción se abre **por reserva** (no global) para que un fallo en una no bloquee el resto del lote. Se usa `Connection::getInstance()` directamente en el servicio porque `Connection` es un singleton: la misma instancia PDO que usan todos los repositorios, por lo que la transacción cubre todas las operaciones del bloque sin necesidad de inyectar PDO explícitamente.

### Manejo de errores

El script CLI captura cualquier `\Throwable` y loguea el mensaje a `/var/log/sigb-cron.log` con timestamp, retornando `exit(1)`. No se usa una excepción personalizada porque:

- El método solo es invocado por el cron job (no por endpoints HTTP).
- Re-lanzar el `\Throwable` original preserva el stack trace completo para debugging.
- El caller (script CLI) ya maneja todo genéricamente; una excepción custom no aportaría información adicional.

## Consecuencias

### Positivas

- **Simple de operar**: se levanta solo con `docker-compose up`.
- **Sin dependencias extra**: no requiere Redis, RabbitMQ ni ningún scheduler externo.
- **Trazabilidad**: toda la actividad queda en `/var/log/sigb-cron.log`.
- **Aislado del proceso web**: un fallo en el cron no afecta la API HTTP.
- **Transacciones atómicas por reserva**: la asignación del ejemplar al siguiente en cola es atómica; no puede quedar en estado inconsistente.

### Negativas

- **Resolución de 30 minutos**: una reserva puede estar técnicamente vencida hasta 30 minutos antes de que el sistema la procese.
- **No hay retry**: si el cron falla en una ejecución, espera la siguiente corrida.
- **Log en contenedor**: `/var/log/sigb-cron.log` existe solo dentro del contenedor; si se elimina el contenedor se pierde el historial.

## Alternativas Descartadas

### Lazy expiration

Verificar el estado al momento de leer la reserva sin persistirlo.

**Descartada porque**: no libera el ejemplar para lectores en cola hasta que otra operación dispare la verificación, lo cual puede demorar indefinidamente si nadie consulta ese artículo.

### Event-driven interno

Emitir un evento al crear la reserva y resolverlo con un scheduler PHP.

**Descartada porque**: requiere infraestructura de mensajería o un scheduler en memoria que no sobrevive reinicios del contenedor, complejidad desproporcionada para el alcance del proyecto.
