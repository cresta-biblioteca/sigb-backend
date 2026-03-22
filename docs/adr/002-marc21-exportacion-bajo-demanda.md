# ADR-002: MARC21 — Generación bajo demanda con scriptotek/marc

## Estado

Aceptada

## Fecha

2026-03-21

## Contexto

El sistema necesita soportar el estándar MARC21 (MAchine-Readable Cataloging), que es el formato de intercambio bibliográfico utilizado por la gran mayoría de bibliotecas a nivel mundial. MARC21 permite compartir registros entre sistemas de gestión (ILS), catálogos colectivos y bases de datos nacionales.

Se analizaron las siguientes dimensiones de diseño:

### 1. ¿Dónde se almacena el registro MARC21?

**Opción A — Columna en la tabla `libro` (pre-generado):**
El registro MARC21 se genera al crear el libro y se guarda como texto en la base de datos.

*Problema:* Si los datos del libro se modifican (`titulo`, `autor`, `editorial`, etc.), el MARC21 almacenado queda desactualizado. Esto genera inconsistencia entre los datos estructurados y el registro MARC21 exportado. Mantenerlos sincronizados requiere regenerar el campo en cada PATCH, lo cual es equivalente a generarlo bajo demanda pero con el costo adicional de almacenamiento redundante.

**Opción B — Generación bajo demanda (sin columna):**
El registro MARC21 se genera en el momento de la solicitud de exportación a partir de los datos actuales del libro.

*Ventajas:*
- Siempre refleja el estado actual de los datos
- Sin redundancia ni riesgo de inconsistencia
- Es el modelo que utilizan los sistemas reales (Koha, DSpace, OCLC)
- La generación es puramente CPU sobre datos ya en memoria, con latencia imperceptible para bibliotecas de tamaño típico (5k–50k registros)

### 2. ¿Biblioteca externa o implementación casera?

**Opción A — Generación manual (DOMDocument / string building):**
Control total del output, pero requiere implementar correctamente el Leader, los indicadores, los subcampos y el encoding de cada formato (MARCXML, ISO 2709).

**Opción B — `scriptotek/marc` + `pear/file_marc`:**
Biblioteca PHP activamente mantenida, usada en proyectos de bibliotecas reales. Abstrae la complejidad del formato binario ISO 2709, garantiza estructuras válidas y reduce la superficie de error.

Por tratarse de un sistema real para una biblioteca, la opción B es la correcta: prioriza estándares de la industria sobre soluciones caseras.

### 3. ¿Qué formatos de exportación?

Las bibliotecas intercambian registros en dos formatos estándar:

- **MARCXML** (ISO 25577): XML estructurado, usado en web services y OAI-PMH
- **ISO 2709** (`.mrc` binario): formato de intercambio universal entre ILS

Ambos son necesarios para interoperabilidad real.

## Decisión

Se implementa **generación bajo demanda** usando **`scriptotek/marc`** como capa de abstracción sobre `pear/file_marc`.

- La columna `export_marc` de la tabla `libro` se elimina mediante migración.
- Se crean endpoints dedicados de exportación (`/libros/{id}/marc21`, `/libros/marc21`).
- La exportación soporta MARCXML y ISO 2709, seleccionables via query param `?format=xml|iso`.
- La exportación masiva procesa los registros en chunks de 200 para evitar problemas de memoria.

## Consecuencias

**Positivas:**
- Los registros exportados siempre son consistentes con los datos actuales
- Soporte para los dos formatos estándar de la industria
- Arquitectura preparada para agregar importación MARC21 (Z39.50, archivos `.mrc`) usando la misma librería
- Sin overhead de almacenamiento ni lógica de sincronización

**Negativas / A considerar:**
- La exportación masiva de catálogos muy grandes (>100k registros) puede requerir procesamiento asíncrono en el futuro
- Los campos MARC21 generados dependen de los datos cargados en el sistema; registros bibliográficos más completos requieren que los bibliotecarios completen todos los campos disponibles
