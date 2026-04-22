# MARC21: Exportación e Importación — Análisis y Plan

## 1. Estado actual: Exportación

### Endpoints existentes

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/libros/{id}/marc21` | Exporta un libro en formato MARC21 (XML o ISO 2709) |
| `GET` | `/libros/marc21` | Exporta libros en bulk con los mismos filtros del listado |

### Análisis del código de exportación

**La implementación es correcta.** Los puntos relevantes:

- **Ordenamiento de rutas** (`routes/libro.php`): `/libros/marc21` se registra antes que `/libros/{id}/marc21` y `/libros/{id}`, evitando que el router interprete "marc21" como un `{id}`. Correcto.
- **Bulk XML**: el streaming chunk-by-chunk (200 registros) funciona bien. El método extrae `<record>` de cada resultado y lo embebe en el `<collection>` raíz.
- **ISO 2709 bulk**: los registros binarios se concatenan directamente en la salida, que es el formato correcto para archivos `.mrc`.
- **Supresión de deprecaciones**: `Marc21Builder::suppressDeprecations()` envuelve correctamente la librería PEAR subyacente.
- **Cabeceras HTTP**: `Content-Type` y `Content-Disposition` son correctas para ambos formatos.

### Mapeo MARC21 implementado (export)

| Campo MARC | Subcampo | Fuente en dominio |
|------------|----------|-------------------|
| 001 | — | `libro.id` |
| 003 | — | Constante `AR-BuSIGB` |
| 008 | pos 00-05 | Fecha actual (creación del registro) |
| 008 | pos 07-10 | `articulo.anio_publicacion` |
| 008 | pos 15-17 | `libro.pais_publicacion` → COUNTRY_MAP (3 chars) |
| 008 | pos 35-37 | `articulo.idioma` → LANGUAGE_MAP (3 chars) |
| 020 | $a | `libro.isbn` |
| 022 | $a | `libro.issn` |
| 041 | $a | `articulo.idioma` → LANGUAGE_MAP |
| 080 | $a | `libro.cdu` |
| 100 | $a, $e | Autor principal (orden=0, rol='autor') — formato "Apellido, Nombre" |
| 245 | $a, $b, $c | `articulo.titulo`, `libro.titulo_informativo`, autor principal |
| 250 | $a | `libro.edicion` |
| 264 | $a, $b, $c | `libro.lugar_de_publicacion`, `libro.editorial`, `articulo.anio_publicacion` |
| 300 | $a, $b, $c | `libro.paginas` + " páginas", `libro.ilustraciones`, `libro.dimensiones` |
| 490 | $a, $v | `libro.serie`, `libro.numero_serie` |
| 500 | $a | `libro.notas` |
| 520 | $a | `articulo.descripcion` |
| 653 | $a | cada tema en `articulo.temas[]` |
| 700 | $a, $e | personas adicionales (no autor principal) |

---

## 2. Plan: Importación MARC21

### 2.1 Objetivo

Permitir cargar uno o múltiples registros bibliográficos en formato MARC21 (XML o ISO 2709) para crear entradas en la base de datos. El caso de uso principal es la **migración desde sistemas legacy** (ej. Koha, WinIBW, AbsysNet).

### 2.2 Nuevo endpoint

```
POST /libros/marc21/import
```

**Importante**: esta ruta debe registrarse en `routes/libro.php` **antes** de `/libros/{id}/marc21`.

### 2.3 Formas de envío (Content-Type)

| Content-Type | Cuándo usarlo |
|---|---|
| `application/xml` | Envío directo de MARCXML en el body |
| `application/marc` | Envío directo de ISO 2709 en el body |
| `multipart/form-data` (campo `file`) | Upload de archivo `.xml` o `.mrc` desde formulario |

El formato también puede indicarse explícitamente con el query param `?format=xml|iso`.
Si el formato no se indica, se detecta automáticamente: si el body comienza con `<?xml` o `<collection`, es XML; de lo contrario, ISO 2709.

### 2.4 Query params del endpoint

| Parámetro | Valores | Default | Descripción |
|---|---|---|---|
| `on_duplicate` | `skip`, `update`, `error` | `skip` | Qué hacer si el ISBN/ISSN ya existe en la BD |
| `mode` | `best_effort`, `strict` | `best_effort` | `strict` = aborta todo ante cualquier error (transacción única). `best_effort` = importa lo que puede y reporta fallos |
| `dry_run` | `true`, `false` | `false` | Valida y reporta sin insertar nada en la BD |
| `tipo_documento_id` | integer | ver nota | ID del tipo de documento a asignar a los artículos creados |

> **Nota `tipo_documento_id`**: MARC21 no codifica el tipo de documento del sistema destino. Se recomienda que el sistema tenga un tipo predeterminado para libros (ej. id=1). Si no se envía, se usa ese default. El cliente puede sobreescribirlo con este parámetro.

### 2.5 Respuesta

Siempre JSON, independientemente del formato de entrada:

```json
{
  "total": 100,
  "imported": 95,
  "skipped": 3,
  "failed": 2,
  "dry_run": false,
  "results": [
    {
      "index": 0,
      "status": "imported",
      "libro_id": 42,
      "isbn": "9781234567890",
      "titulo": "Introducción a la bibliotecología"
    },
    {
      "index": 5,
      "status": "skipped",
      "isbn": "9780000000000",
      "reason": "ISBN ya existe (libro_id: 15)"
    },
    {
      "index": 8,
      "status": "failed",
      "reason": "Campo 245 $a (título) ausente o vacío"
    }
  ]
}
```

En modo `dry_run=true`, los `status` posibles son `would_import`, `would_skip`, `would_fail`.

### 2.6 Mapeo inverso: MARC21 → Dominio

La importación es el proceso inverso del `Marc21Builder`. Reglas de mapeo:

| Campo MARC | Subcampo | → Modelo | Notas |
|------------|----------|----------|-------|
| 001 | — | ignorado | Se genera nuevo ID en la BD |
| 003 | — | ignorado | |
| 008 | pos 07-10 | `articulo.anio_publicacion` | Fallback si no hay 264 $c |
| 008 | pos 15-17 | `libro.pais_publicacion` | Inverso de COUNTRY_MAP → código ISO 2 chars |
| 008 | pos 35-37 | `articulo.idioma` | Fallback si no hay 041. Inverso de LANGUAGE_MAP |
| 020 | $a | `libro.isbn` | Strip guiones/espacios antes de validar |
| 022 | $a | `libro.issn` | |
| 041 | $a | `articulo.idioma` | Prioritario sobre 008. Inverso de LANGUAGE_MAP |
| 080 | $a | `libro.cdu` | Parsear como entero |
| 100 | $a | persona (autor, orden=0) | Formato "Apellido, Nombre" → split |
| 245 | $a | `articulo.titulo` | **Obligatorio** |
| 245 | $b | `libro.titulo_informativo` | |
| 250 | $a | `libro.edicion` | |
| 264 | $a | `libro.lugar_de_publicacion` | |
| 264 | $b | `libro.editorial` | |
| 264 | $c | `articulo.anio_publicacion` | Prioritario sobre 008 |
| 300 | $a | `libro.paginas` | Extraer número: `preg_match('/(\d+)/', ...)` |
| 300 | $b | `libro.ilustraciones` | |
| 300 | $c | `libro.dimensiones` | |
| 490 | $a | `libro.serie` | |
| 490 | $v | `libro.numero_serie` | |
| 500 | $a | `libro.notas` | Si hay múltiples 500, concatenar con "; " |
| 520 | $a | `articulo.descripcion` | |
| 653 | $a | temas | Buscar por título; crear si no existe |
| 700 | $a | persona adicional | Formato "Apellido, Nombre" → split |
| 700 | $e | rol de la persona | Mapear a valores válidos del sistema |

#### Mapas inversos necesarios

```php
// LANGUAGE_MAP inverso: 3-char MARC → 2-char ISO
private const INVERSE_LANGUAGE_MAP = [
    'spa' => 'es',
    'eng' => 'en',
    'por' => 'pt',
    'fre' => 'fr',
    'ger' => 'de',
    'ita' => 'it',
];

// COUNTRY_MAP inverso: 3-char MARC → 2-char ISO
private const INVERSE_COUNTRY_MAP = [
    'ag ' => 'ar',
    'xxu' => 'us',
    'mx ' => 'mx',
    'sp ' => 'es',
    'xxk' => 'gb',
    'bl ' => 'br',
    'cl ' => 'cl',
    'ck ' => 'co',
    'pe ' => 'pe',
    'uy ' => 'uy',
    'fr ' => 'fr',
    'gw ' => 'de',
    'it ' => 'it',
    'po ' => 'pt',
];
```

#### Parsing de personas

El formato MARC "Apellido, Nombre" debe descomponerse:

```
"García López, Juan Carlos" → apellido="García López", nombre="Juan Carlos"
"Borges, Jorge Luis"         → apellido="Borges",      nombre="Jorge Luis"
"UNESCO"                      → apellido="UNESCO",      nombre=null  (entidad corporativa)
```

Regla: split en la primera coma. Si no hay coma, tratar como entidad corporativa (apellido=nombre completo, nombre=null).

Antes de crear una persona, buscar si ya existe con el mismo `nombre`+`apellido` para reutilizar el registro (evitar duplicados).

#### Temas (653 $a)

Buscar en la tabla de temas por `titulo` (case-insensitive). Si no existe, crear uno nuevo. Asociarlo al artículo recién creado.

### 2.7 Clases a crear

```
src/Catalogo/Libros/Marc21/
├── Marc21ExportController.php   (existente)
├── Marc21ExportService.php      (existente)
├── Marc21Builder.php            (existente)
├── Marc21ImportController.php   (NUEVO)
├── Marc21ImportService.php      (NUEVO)
├── Marc21Parser.php             (NUEVO)
└── Marc21ImportResultDTO.php    (NUEVO)
```

**`Marc21Parser`**: recibe el contenido crudo (string) y el formato (`xml` o `iso`). Devuelve un array de arrays asociativos con los campos extraídos (representación intermedia agnóstica del dominio). Usa `File_MARC_XML` para XML y `File_MARC` para ISO 2709.

**`Marc21ImportService`**: orquesta el proceso. Para cada registro parseado:
1. Valida campos mínimos (245 $a).
2. Detecta duplicados por ISBN/ISSN.
3. Según `on_duplicate`, decide si importar/saltar/fallar.
4. Crea Articulo → Libro → Personas → Temas usando los repositorios/servicios existentes.
5. Acumula resultados.

**`Marc21ImportController`**: maneja el HTTP. Detecta formato, extrae contenido (body directo o multipart), delega al service, retorna JSON.

**`Marc21ImportResultDTO`**: representa el resultado de intentar importar un registro individual.

### 2.8 Campos requeridos vs. opcionales en el dominio

El modelo de `Articulo` tiene campos **no anulables** que pueden estar ausentes en un registro MARC:

| Campo del dominio | ¿Anulable? | Fuente MARC | Comportamiento si falta |
|---|---|---|---|
| `articulo.titulo` | No | 245 $a | Registro `failed` |
| `articulo.anio_publicacion` | No | 264 $c ó 008[07-10] | Registro `failed` si ambos ausentes o inválidos |
| `articulo.tipo_documento_id` | No | Leader pos 6+7 (ver §2.9) | Registro `failed` si no resuelve |
| `articulo.idioma` | No, pero default `'es'` | 041 $a ó 008[35-37] | Fallback a `'es'` + warning en resultado si código desconocido |
| `libro.isbn` | Sí | 020 $a | `null` |
| `libro.paginas` | Sí | 300 $a | `null` |
| Cualquier otro campo de Libro | Sí | varios | `null` |

**Regla general**: si un campo MARC opcional está ausente, el campo del modelo se asigna como `null`. Solo los campos no anulables del dominio pueden convertir un registro en `failed`.

### 2.9 Tipo de documento: MARC21 no es solo para libros

MARC21 es un estándar bibliográfico **genérico**. El tipo de material está codificado en el **Leader**, posiciones 6 y 7:

| Leader pos 6+7 | Tipo de material |
|---|---|
| `a` + `m` | Monografía (libro) |
| `a` + `s` | Serial / Revista |
| `e` | Material cartográfico (mapa) |
| `g` | Material proyectado (película, video) |
| `j` | Grabación musical |
| `m` | Archivo de computadora |

El `Marc21Builder` fija el leader como `'00000nam a2200000 i 4500'` (tipo `a`, nivel `m`), acotando correctamente la exportación a monografías.

**Para la importación**, el proceso de resolución del `tipo_documento_id` debe ser:

1. Leer Leader pos 6 (`'a'`) y pos 7 (`'m'`) del registro MARC entrante.
2. Intentar mapear a un `codigo` de `TipoDocumento` en la base de datos (ej. Leader `'am'` → código `'LIB'`).
3. Si hay coincidencia → usar ese `tipo_documento_id`.
4. Si no hay equivalente configurado → rechazar el registro: *"Tipo de material MARC 'as' (serial) no tiene tipo_documento configurado en el sistema"*.
5. Si se envía `?tipo_documento_id=N` en el request → sobrescribe la detección automática.

**Consecuencia arquitectural**: el importador actual (`src/Catalogo/Libros/Marc21/`) debe validar que el Leader indique monografía (`'am'`). Si un archivo de migración contiene revistas mezcladas con libros, el importador debe:
- Aceptar los libros (`am`).
- Rechazar (o separar) los demás con un mensaje descriptivo.

Cuando se implemente otro tipo de documento (ej. `Revista`), tendrá su propio importador bajo `src/Catalogo/Revistas/Marc21/`, siguiendo el mismo patrón.

### 2.10 Validaciones y errores esperados

| Condición | Comportamiento |
|---|---|
| Archivo vacío | HTTP 400, mensaje de error claro |
| XML malformado | HTTP 400, con el error de parsing |
| ISO 2709 inválido | HTTP 400 |
| Archivo demasiado grande | HTTP 413 (configurar en PHP/Apache también) |
| Campo 245 $a ausente o vacío | Registro marcado como `failed` |
| `anio_publicacion` ausente (sin 264$c ni 008[07-10] válido) | Registro marcado como `failed` |
| Leader indica tipo no soportado (ej. serial) | Registro marcado como `failed` con tipo indicado |
| ISBN inválido (formato) | Registro marcado como `failed` |
| CDU no numérica | Ignorar el campo (no es crítico), asignar `null` |
| Idioma MARC sin equivalente en LANGUAGE_MAP | Fallback a `'es'`, `status: imported` con `warnings: [...]` |
| ISBN ya existe + `on_duplicate=error` | Registro marcado como `failed` |
| ISBN ya existe + `on_duplicate=skip` | Registro marcado como `skipped` |
| Error de BD durante insert | Registro marcado como `failed`, continuar con el siguiente (si `best_effort`) |

### 2.11 Seguridad

- **XXE**: al parsear XML, deshabilitar entidades externas (`libxml_disable_entity_loader(true)` o usar flags `LIBXML_NONET`).
- **Tamaño de archivo**: validar `Content-Length` antes de leer el body; configurar `upload_max_filesize` y `post_max_size` en PHP.
- **Sanitización**: los campos de texto extraídos de MARC deben sanitizarse antes de persistir (trim, validación de longitud máxima del modelo).
- **Autorización**: el endpoint debe requerir autenticación (`bearerAuth`) al igual que los de exportación.

### 2.12 Consideraciones para archivos grandes (migración masiva)

- El `Marc21Parser` debe poder procesar los registros uno a uno (streaming), no cargar todo en memoria.
  - Para ISO 2709: `File_MARC` itera natively registro por registro.
  - Para MARCXML: usar `XMLReader` en lugar de `SimpleXML`/`DOMDocument`.
- Para migraciones de miles de registros, considerar un endpoint asíncrono en el futuro (upload → job en cola → webhook/poll de estado). En primera versión, el procesamiento sincrónico es suficiente con un timeout alto.
- Documentar en el README del proyecto los ajustes de PHP necesarios: `max_execution_time`, `memory_limit`, `upload_max_filesize`, `post_max_size`.

### 2.13 Tests

| Test | Tipo |
|---|---|
| Parseo de MARCXML válido con 1 registro | Unit (`Marc21Parser`) |
| Parseo de MARCXML con múltiples registros | Unit |
| Parseo de ISO 2709 válido | Unit |
| Parseo de XML malformado → excepción | Unit |
| Mapeo correcto de campos al dominio | Unit (`Marc21ImportService`) |
| Import exitoso de 1 libro vía XML | Integration |
| Import bulk de N libros | Integration |
| Duplicado ISBN con `on_duplicate=skip` | Integration |
| Duplicado ISBN con `on_duplicate=update` | Integration |
| Duplicado ISBN con `on_duplicate=error` | Integration |
| `dry_run=true` no persiste nada | Integration |
| Registro sin 245 $a → `failed` | Integration |
| Personas deduplicadas correctamente | Integration |
| Temas creados si no existen | Integration |

---

## 3. Orden de implementación sugerido

1. **`Marc21Parser`** — aislado, testeable sin BD.
2. **Tests unitarios de `Marc21Parser`** — con fixtures XML e ISO.
3. **`Marc21ImportService`** (sin `on_duplicate=update` inicialmente, solo skip/error).
4. **`Marc21ImportController`** + registro de ruta.
5. **Tests de integración** del flujo completo.
6. **`on_duplicate=update`** — último porque requiere lógica de merge de datos.
7. **Modo asíncrono** — trabajo futuro, fuera de alcance inicial.

---

## 4. Contexto: tipos de documento en sistemas de bibliotecas reales

### La distinción que todo SIGB maneja

Los sistemas de gestión bibliotecaria separan dos conceptos que parecen uno pero son independientes:

#### Tipo de material (catalogación / MARC)
Viene del **Leader** MARC. Describe qué es físicamente el documento:
- Monografía, Serial, Mapa, Grabación musical, etc.
- Determina qué campos MARC aplican al registro.
- Lo interpreta el catalogador y los sistemas de intercambio.

#### Tipo de ítem (circulación / políticas)
Definido por cada biblioteca. Describe **cómo se presta**:
- "Libro general", "Libro de referencia", "DVD", "Tesis"
- Determina: ¿es renovable?, ¿cuántos días?, ¿cuántos por usuario?
- Lo configura el bibliotecario según sus reglas internas.

**El `TipoDocumento` de SIGB (que tiene campo `renovable`) corresponde al segundo**: es exactamente lo que Koha llama `itemtypes`. No es el tipo de material MARC. Ambos pueden coexistir en el mismo registro sin conflicto:

```
MARC Leader 'am'  →  "esto es una monografía"     (catalogación, interoperabilidad)
TipoDocumento     →  "préstamo 14 días, renovable"  (circulación, política interna)
```

Un libro de referencia y una novela son ambos monografías MARC (`am`), pero tienen `TipoDocumento` diferente.

### Cómo maneja Koha los diferentes tipos MARC

Koha **no** segrega los tipos en tablas separadas. Usa un modelo plano:

```
biblio          → título, autor, año, idioma (campos extraídos del MARC para búsqueda rápida)
biblioitems     → ISBN, editorial, páginas, itemtype → todos en la misma tabla
                  + marcxml (el registro MARC completo almacenado como blob XML)
```

Todo tipo de material va a las mismas tablas. El `itemtype` apunta a las reglas de circulación. El Leader MARC queda dentro del blob XML, sin columna propia.

SIGB toma un camino diferente (modelo de dominio tipado: `Articulo` + `Libro`), lo que es más limpio pero requiere decisiones explícitas al importar materiales de tipo no soportado.

### Qué trae un catálogo de Koha 2010

Al exportar el catálogo del Koha de origen a MARCXML, la distribución típica de una biblioteca universitaria o pública es:

| Tipo MARC | Leader | Frecuencia típica | Estado en SIGB |
|---|---|---|---|
| Monografía (libro) | `am` | ~85-90% | Soportado (→ `Libro`) |
| Serial / Revista | `as` | ~5-10% | No soportado aún |
| Material audiovisual | `gm` | < 5% | No soportado aún |
| Tesis / informe | `am` con campo 502 | < 3% | Se importa como libro (correcto) |
| Mapa | `em` | < 1% | No soportado aún |

> **Nota**: las revistas en Koha suelen gestionarse con el módulo Serials, cuyo catálogo **no** siempre forma parte del export MARCXML estándar del catálogo de monografías. Verificar con la biblioteca qué módulos usaban activamente.

### Estrategia para la migración

Para tipos de material no soportados hay tres opciones. La recomendada para la primera versión es **la A**:

**Opción A — Rechazar con informe (recomendada v1)**
El importer acepta solo registros con Leader `am`. Rechaza el resto con mensaje descriptivo. El informe final indica cuántos registros de cada tipo no-soportado se encontraron, para que el bibliotecario pueda migrarlos manualmente o decidir ignorarlos. Simple, sin riesgos de datos corruptos.

**Opción B — Articulo genérico sin sub-entidad**
Para tipos no soportados se crea solo el `Articulo` (titulo, año, idioma, tipo_documento_id) sin tabla secundaria. Los campos tipo-específicos se descartan o van a `notas`. El catálogo queda completo para búsquedas, con datos parciales.

**Opción C — Modelar cada tipo (trabajo futuro)**
Cuando sea necesario, agregar `Revista` bajo `src/Catalogo/Revistas/` con su propio `Marc21Builder`/`Marc21Parser`. Cada tipo maneja sus propios campos MARC específicos.

### Recomendación adicional: columna `marc_tipo_material`

Independientemente de la estrategia elegida, conviene agregar una columna en `articulos` que capture el tipo de material MARC del registro original:

```sql
ALTER TABLE articulos ADD COLUMN marc_tipo_material CHAR(2) NULL COMMENT 'Código Leader pos6+7 del registro MARC origen (ej: am, as)';
```

Esto permite:
- Reportes post-migración: "cuántos registros son libros vs. otros tipos"
- Facilitar la implementación futura de importers adicionales
- Trazabilidad del origen del registro sin necesidad de guardar el MARC completo

No requiere cambios en ninguna lógica existente y se puede agregar en una migración independiente.

---

## 5. Fixture de ejemplo para tests

### MARCXML mínimo válido

```xml
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
  <record>
    <leader>00000nam a2200000 i 4500</leader>
    <controlfield tag="003">AR-BuSIGB</controlfield>
    <datafield tag="020" ind1=" " ind2=" ">
      <subfield code="a">9789876543210</subfield>
    </datafield>
    <datafield tag="041" ind1="0" ind2=" ">
      <subfield code="a">spa</subfield>
    </datafield>
    <datafield tag="100" ind1="1" ind2=" ">
      <subfield code="a">Martínez, Ana</subfield>
      <subfield code="e">autor</subfield>
    </datafield>
    <datafield tag="245" ind1="1" ind2="0">
      <subfield code="a">Fundamentos de catalogación</subfield>
      <subfield code="b">una introducción práctica</subfield>
    </datafield>
    <datafield tag="264" ind1=" " ind2="1">
      <subfield code="a">Buenos Aires</subfield>
      <subfield code="b">Alfagrama</subfield>
      <subfield code="c">2020</subfield>
    </datafield>
    <datafield tag="300" ind1=" " ind2=" ">
      <subfield code="a">320 páginas</subfield>
    </datafield>
    <datafield tag="520" ind1=" " ind2=" ">
      <subfield code="a">Manual introductorio sobre teoría y práctica de catalogación bibliográfica.</subfield>
    </datafield>
    <datafield tag="653" ind1=" " ind2=" ">
      <subfield code="a">catalogación</subfield>
    </datafield>
    <datafield tag="653" ind1=" " ind2=" ">
      <subfield code="a">bibliotecología</subfield>
    </datafield>
  </record>
</collection>
```
