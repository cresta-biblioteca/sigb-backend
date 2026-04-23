# MARC21 — Referencia Tecnica

> Para conceptos basicos de MARC21 y campos pendientes de consulta con el cliente, ver [guia_de_uso_marc21.md](guia_de_uso_marc21.md).

## Indice

1. [Qué es MARC21](#1-qué-es-marc21)
2. [Arquitectura de la implementación](#2-arquitectura-de-la-implementación)
3. [Librería: scriptotek/marc](#3-librería-scriptotkmarc)
4. [Marc21Builder — mapeo de campos](#4-marc21builder--mapeo-de-campos)
5. [Endpoints de exportación](#5-endpoints-de-exportación)
6. [Formatos de salida](#6-formatos-de-salida)
7. [Exportación masiva](#7-exportación-masiva)
8. [Extensibilidad futura](#8-extensibilidad-futura)

---

## 1. Qué es MARC21

MARC21 (MAchine-Readable Cataloging) es el estándar internacional para la representación y comunicación de información bibliográfica. Es usado por la gran mayoría de bibliotecas del mundo para:

- Intercambiar registros entre sistemas de gestión bibliotecaria (ILS)
- Participar en catálogos colectivos (ROAI, catálogos nacionales)
- Exportar e importar registros en masa
- Comunicarse via protocolos como Z39.50 y OAI-PMH

Un registro MARC21 se compone de:
- **Leader**: 24 caracteres fijos que describen el tipo y estado del registro
- **Campos de control** (00X): datos fijos como el identificador del registro
- **Campos de datos** (1XX–9XX): información bibliográfica estructurada en subcampos

Cada campo de datos tiene dos **indicadores** (pueden ser blancos o dígitos) y uno o más **subcampos** identificados por un código de letra.

---

## 2. Arquitectura de la implementación

```
src/Catalogo/Libros/Marc21/
├── Marc21Builder.php          # Construye el registro MARC21 desde un Libro
├── Marc21ExportService.php    # Orquesta exportación individual y masiva
└── Marc21ExportController.php # Expone los endpoints HTTP
```

El flujo de exportación es:

```
Request HTTP
    │
    ▼
Marc21ExportController
    │ valida id / params
    ▼
Marc21ExportService
    │ obtiene Libro+Articulo+Personas del repositorio
    ▼
Marc21Builder::build(Libro)
    │ construye File_MARC_Record
    │ lo envuelve en Scriptotek\Marc\Record
    ▼
::toXML() o ::toRaw()
    │
    ▼
Response (stream)
```

El registro MARC21 **nunca se almacena en la base de datos**. Se genera en el momento de la solicitud a partir de los datos actuales del libro. Esto garantiza que el registro exportado siempre sea consistente con los datos del catálogo.

---

## 3. Librería: scriptotek/marc

**Repositorio:** https://github.com/scriptotek/php-marc
**Versión utilizada:** `^3.0`
**Dependencias transitivas:** `pear/file_marc`, `ck/php-marcspec`

La librería `scriptotek/marc` actúa como wrapper de alto nivel sobre `pear/file_marc`. En esta implementación se usa la capa de `pear/file_marc` para la **construcción** de registros y `scriptotek/marc` para el **wrapping** y la **serialización**.

### Clases relevantes

| Clase | Paquete | Uso |
|-------|---------|-----|
| `File_MARC_Record` | `pear/file_marc` | Registro MARC21 mutable |
| `File_MARC_Control_Field` | `pear/file_marc` | Campos de control (001, 003, 008) |
| `File_MARC_Data_Field` | `pear/file_marc` | Campos de datos (100, 245, etc.) |
| `File_MARC_Subfield` | `pear/file_marc` | Subcampos (`$a`, `$b`, `$c`, etc.) |
| `Scriptotek\Marc\Record` | `scriptotek/marc` | Wrapper con `toXML()` y `toRaw()` |

### Serialización

```php
$record->toXML();   // MARCXML (ISO 25577) como string XML
$record->toRaw();   // ISO 2709 binario (.mrc) como string binario
```

---

## 4. Marc21Builder — mapeo de campos

`Marc21Builder::build(Libro $libro): Record` toma un `Libro` con su `Articulo` y `Personas` cargados y construye el registro MARC21 completo.

### Tabla de mapeo

| Tag MARC21 | Nombre | Indicadores | Subcampos | Fuente en el modelo |
|------------|--------|-------------|-----------|---------------------|
| `001` | Identificador de registro | — | — | `Libro::getId()` |
| `003` | Código de organización | — | — | Fijo: `AR-BuSIGB` |
| `008` | Datos fijos | — | — | Construido desde `Articulo`, `Libro` (40 chars) |
| `020` | ISBN | `' '` `' '` | `$a` ISBN | `Libro::getIsbn()` |
| `022` | ISSN | `'0'` `' '` | `$a` ISSN | `Libro::getIssn()` |
| `041` | Código de idioma | `'0'` `' '` | `$a` código ISO 639-2 | `Articulo::getIdioma()` (mapeado) |
| `080` | Clasificación CDU (UDC) | `' '` `' '` | `$a` número CDU | `Libro::getCdu()` |
| `100` | Autor principal | `'1'` `' '` | `$a` "Apellido, Nombre", `$e` "autor" | `Libro::getAutorPrincipal()` |
| `245` | Mención de título | `'1'` `'0'` | `$a` título, `$b` subtítulo, `$c` autor | `Articulo::getTitulo()`, `Libro::getTituloInformativo()`, autor principal |
| `250` | Edición | `' '` `' '` | `$a` edición | `Libro::getEdicion()` |
| `264` | Publicación | `' '` `'1'` | `$a` lugar, `$b` editorial, `$c` año | `Libro::getLugarDePublicacion()`, `Libro::getEditorial()`, `Articulo::getAnioPublicacion()` |
| `300` | Descripción física | `' '` `' '` | `$a` páginas, `$b` ilustraciones, `$c` dimensiones | `Libro::getPaginas()`, `Libro::getIlustraciones()`, `Libro::getDimensiones()` |
| `490` | Serie | `'0'` `' '` | `$a` serie, `$v` número | `Libro::getSerie()`, `Libro::getNumeroSerie()` |
| `500` | Notas | `' '` `' '` | `$a` texto | `Libro::getNotas()` |
| `520` | Resumen | `' '` `' '` | `$a` texto | `Articulo::getDescripcion()` |
| `650` | Materias | `' '` `'4'` | `$a` materia (uno por cada) | `Articulo::getMaterias()` |
| `653` | Temas | `' '` `' '` | `$a` tema (uno por cada) | `Articulo::getTemas()` |
| `700` | Personas adicionales | `'1'` `' '` | `$a` "Apellido, Nombre", `$e` rol | Personas excluyendo autor principal |

### Campo 008 — Datos fijos (40 caracteres)

| Posición | Contenido | Fuente |
|----------|-----------|--------|
| 00-05 | Fecha de creación (yymmdd) | `date('ymd')` |
| 06 | Tipo de fecha: `s` (fecha única) | Fijo |
| 07-10 | Año de publicación | `Articulo::getAnioPublicacion()` |
| 11-14 | Espacios | — |
| 15-17 | País de publicación (código MARC) | `Libro::getPaisPublicacion()` mapeado |
| 18-21 | Ilustraciones | `Libro::getIlustraciones()` ? `a   ` : `    ` |
| 22-34 | Defaults/espacios | — |
| 35-37 | Código de idioma (ISO 639-2) | `Articulo::getIdioma()` mapeado |
| 38 | Registro modificado: espacio | — |
| 39 | Fuente de catalogación: `d` | Fijo |

### Mapeo de idiomas

| Código del sistema | Código MARC21 (ISO 639-2) |
|--------------------|--------------------------|
| `es` | `spa` |
| `en` | `eng` |
| `pt` | `por` |
| `fr` | `fre` |
| `de` | `ger` |
| `it` | `ita` |

### Mapeo de países

| Código ISO | Código MARC |
|------------|-------------|
| `ar` | `ag ` |
| `us` | `xxu` |
| `mx` | `mx ` |
| `es` | `sp ` |
| `gb` | `xxk` |
| `br` | `bl ` |
| `cl` | `cl ` |
| `co` | `ck ` |
| `pe` | `pe ` |
| `uy` | `uy ` |
| `fr` | `fr ` |
| `de` | `gw ` |
| `it` | `it ` |
| `pt` | `po ` |

### Campos opcionales

Todos los campos excepto `001`, `003` y `245` son opcionales. Si el dato no está cargado en el modelo, el campo simplemente no se incluye en el registro. Esto es válido en MARC21.

### Modelo de personas

Los autores y colaboradores se almacenan en una tabla normalizada `persona` (nombre, apellido) con una tabla pivote `libro_persona` que incluye el rol y orden. Esto permite:

- Generar el campo 100 con formato correcto "Apellido, Nombre"
- Generar campos 700 individuales por cada persona adicional
- Deduplicar personas compartidas entre libros
- Soportar roles múltiples (autor, coautor, colaborador, editor, traductor, ilustrador)

---

## 5. Endpoints de exportación

### Exportación individual

```
GET /api/v1/libros/{id}/marc21
```

**Query params:**

| Param | Valores | Default | Descripción |
|-------|---------|---------|-------------|
| `format` | `xml`, `iso` | `xml` | Formato de salida |

**Ejemplos:**

```bash
# MARCXML (default)
curl -H "Authorization: Bearer {token}" \
     https://api.sigb.example/api/v1/libros/42/marc21

# ISO 2709 binario
curl -H "Authorization: Bearer {token}" \
     https://api.sigb.example/api/v1/libros/42/marc21?format=iso \
     -o libro-42.mrc
```

**Headers de respuesta:**

| Format | Content-Type | Content-Disposition |
|--------|--------------|---------------------|
| `xml` | `application/xml; charset=UTF-8` | `attachment; filename="libro-{id}.xml"` |
| `iso` | `application/marc` | `attachment; filename="libro-{id}.mrc"` |

**Errores:**

| Código HTTP | Motivo |
|-------------|--------|
| `404` | El libro no existe |
| `400` | ID inválido |
| `401` | Token ausente o inválido |

---

### Exportación masiva

```
GET /api/v1/libros/marc21
```

Exporta todos los libros que coincidan con los filtros de búsqueda estándar del catálogo. Procesa los registros en chunks de 200 para evitar problemas de memoria.

**Query params:**

Acepta todos los filtros del endpoint `GET /api/v1/libros`, más:

| Param | Valores | Default | Descripción |
|-------|---------|---------|-------------|
| `format` | `xml`, `iso` | `xml` | Formato de salida |

**Ejemplos:**

```bash
# Todos los libros en español, formato MARCXML
curl -H "Authorization: Bearer {token}" \
     "https://api.sigb.example/api/v1/libros/marc21?idioma=es" \
     -o catalogo-es.xml

# Libros de una persona específica en ISO 2709
curl -H "Authorization: Bearer {token}" \
     "https://api.sigb.example/api/v1/libros/marc21?persona=Martin&format=iso" \
     -o libros-martin.mrc

# Todo el catálogo en MARCXML
curl -H "Authorization: Bearer {token}" \
     "https://api.sigb.example/api/v1/libros/marc21" \
     -o catalogo-completo.xml
```

**Formato de salida MARCXML (colección):**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
  <record>
    <leader>00000nam a2200000 i 4500</leader>
    <controlfield tag="001">42</controlfield>
    <controlfield tag="003">AR-BuSIGB</controlfield>
    <controlfield tag="008">260322s2008    ag a          000 0 eng d</controlfield>
    <datafield tag="020" ind1=" " ind2=" ">
      <subfield code="a">9780132350884</subfield>
    </datafield>
    <datafield tag="041" ind1="0" ind2=" ">
      <subfield code="a">eng</subfield>
    </datafield>
    <datafield tag="100" ind1="1" ind2=" ">
      <subfield code="a">Martin, Robert C.</subfield>
      <subfield code="e">autor</subfield>
    </datafield>
    <datafield tag="245" ind1="1" ind2="0">
      <subfield code="a">Clean Code</subfield>
      <subfield code="b">A Handbook of Agile Software Craftsmanship</subfield>
      <subfield code="c">Martin, Robert C.</subfield>
    </datafield>
    <datafield tag="250" ind1=" " ind2=" ">
      <subfield code="a">1st ed.</subfield>
    </datafield>
    <datafield tag="264" ind1=" " ind2="1">
      <subfield code="a">Upper Saddle River</subfield>
      <subfield code="b">Prentice Hall</subfield>
      <subfield code="c">2008</subfield>
    </datafield>
    <datafield tag="300" ind1=" " ind2=" ">
      <subfield code="a">431 páginas</subfield>
      <subfield code="c">24 cm</subfield>
    </datafield>
    <datafield tag="650" ind1=" " ind2="4">
      <subfield code="a">Ingeniería de Software</subfield>
    </datafield>
    <datafield tag="653" ind1=" " ind2=" ">
      <subfield code="a">Clean Code</subfield>
    </datafield>
    <datafield tag="700" ind1="1" ind2=" ">
      <subfield code="a">Feathers, Michael C.</subfield>
      <subfield code="e">colaborador</subfield>
    </datafield>
  </record>
  <!-- ... más registros -->
</collection>
```

---

## 6. Formatos de salida

### MARCXML (ISO 25577)

Formato XML estructurado, legible por humanos y máquinas. Es el formato preferido para:
- Intercambio via web services
- OAI-PMH (Open Archives Initiative Protocol for Metadata Harvesting)
- Importación en sistemas modernos
- Inspección visual y debugging

El elemento raíz `<collection>` envuelve múltiples `<record>` en exportaciones masivas. En exportaciones individuales, el elemento raíz es directamente `<record>`.

### ISO 2709 (`.mrc` binario)

Formato binario de intercambio de referencia, el más antiguo y universalmente soportado. Es necesario para:
- Importar registros en ILS como Koha, Alma, Sierra
- Herramientas de catalogación como MARCEdit
- Intercambio con catálogos colectivos nacionales

El archivo `.mrc` resultante puede abrirse directamente en MARCEdit para inspección y edición.

---

## 7. Exportación masiva

### Mecanismo de streaming

La exportación masiva no carga todos los registros en memoria a la vez. Utiliza paginación interna con chunks de 200 registros:

```
chunk 1 (200 libros) → generar MARC → enviar al cliente
chunk 2 (200 libros) → generar MARC → enviar al cliente
...
chunk N              → generar MARC → enviar al cliente
```

Esto permite exportar catálogos de cualquier tamaño sin agotar la memoria del servidor.

### Consideraciones para catálogos muy grandes

Para catálogos de más de 50.000 registros, se recomienda en el futuro implementar:
- Exportación asíncrona (job en background + descarga del archivo resultante)
- Compresión de la respuesta (`Content-Encoding: gzip`)

---

## 8. Extensibilidad futura

### Importación MARC21

La librería `scriptotek/marc` soporta también la **lectura** de registros MARC21. Esto habilita:

```php
use Scriptotek\Marc\Collection;

// Importar desde archivo ISO 2709
$collection = Collection::fromFile('registros.mrc');
foreach ($collection as $record) {
    $titulo = (string) $record->title;
    $isbn   = (string) $record->isbn;
    // ... mapear a Libro y persistir
}

// Importar desde MARCXML
$collection = Collection::fromFile('registros.xml');
```

Esto permitiría implementar un endpoint de importación masiva (`POST /libros/import/marc21`) que procese archivos `.mrc` o `.xml` y cargue los registros al catálogo.

### OAI-PMH

El protocolo OAI-PMH permite que otros sistemas "cosechan" (harvesting) registros del catálogo de forma incremental. La generación bajo demanda implementada es la base necesaria para agregar soporte OAI-PMH en el futuro.

### Z39.50

Protocolo estándar de búsqueda y recuperación bibliográfica. Permite que otros sistemas consulten el catálogo y descarguen registros MARC21 directamente. Requiere un servidor Z39.50 separado (como YAZ) que consumiría los mismos datos via la API.
