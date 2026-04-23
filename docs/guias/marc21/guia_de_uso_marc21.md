# Guia MARC21 - SIGB

Este documento explica como nuestro sistema traduce los objetos `Libro` de la base de datos relacional al estandar internacional **MARC21** y lista las mejoras pendientes de consulta con el cliente.

> Para la referencia tecnica completa (mapeo de campos, endpoints, formatos de salida), ver [marc21-impl.md](marc21-impl.md).

---

## 1. Conceptos Fundamentales

MARC21 no es un archivo, es un **diccionario semantico universal**.

* **Tag (Etiqueta):** Codigo de 3 digitos que define el tipo de dato (Ej: 245 = Titulo).
* **Indicadores:** Dos posiciones (Ind1 e Ind2) que actuan como "switches" de configuracion para el tag.
* **Subcampos:** Letras (a, b, c...) que fragmentan la informacion dentro de un tag.

> **La Regla de Oro:** El significado de los Indicadores y Subcampos **DEPENDE** del Tag. El subcampo `$a` en el tag `100` es un Autor, pero en el tag `245` es un Titulo.

---

## 2. Anatomia de un Registro en nuestro Modulo

### A. Campos de Control (00X)
Son campos simples, sin indicadores ni subcampos. Se usan para IDs y metadatos fijos.
* `001`: ID del libro en nuestra base de datos.
* `003`: Codigo de organizacion (`AR-BuSIGB`).
* `008`: Campo de posicion fija (40 caracteres). Contiene fecha de creacion, pais e idioma en posiciones especificas.

### B. Campos de Datos (1XX - 9XX)
Tienen la estructura: `TAG` + `IND1` + `IND2` + `SUBFIELDS`.

#### Ejemplo visual (Tag 245 - Titulo):
```xml
<datafield tag="245" ind1="1" ind2="0">
  <subfield code="a">Algebra Lineal</subfield>
  <subfield code="b">Conceptos y aplicaciones</subfield>
  <subfield code="c">Lay, David C.</subfield>
</datafield>
```

### C. Modelo de Personas

Los autores y colaboradores se almacenan en una tabla normalizada `persona` (nombre, apellido) con una tabla pivote `libro_persona` (rol, orden). Esto permite:

- Generar el campo 100 con formato correcto "Apellido, Nombre"
- Generar campos 700 individuales por cada persona adicional
- Deduplicar personas compartidas entre libros
- Soportar roles multiples (autor, coautor, colaborador, editor, traductor, ilustrador)

---

## 3. Campos implementados actualmente

| Tag | Nombre | Obligatorio |
|-----|--------|:-----------:|
| 001 | Identificador de registro | Si |
| 003 | Codigo de organizacion | Si |
| 008 | Datos fijos (40 chars) | Si |
| 020 | ISBN | No |
| 022 | ISSN | No |
| 041 | Codigo de idioma | No |
| 080 | Clasificacion CDU | No |
| 100 | Autor principal | No |
| 245 | Titulo | Si |
| 250 | Edicion | No |
| 264 | Publicacion (lugar, editorial, anio) | No |
| 300 | Descripcion fisica (paginas, ilustraciones, dimensiones) | No |
| 490 | Serie | No |
| 500 | Notas | No |
| 520 | Resumen | No |
| 650 | Materias | No |
| 653 | Temas | No |
| 700 | Personas adicionales | No |

---

## 4. TODO - Campos opcionales (requieren consulta con el cliente)

Los siguientes campos son parte del estandar MARC21 y mejorarian la calidad de los registros exportados, pero **no deben implementarse sin antes confirmar con la biblioteca si los necesitan o utilizan**.

### 4.1 Campo 005 — Timestamp de ultima modificacion

**Que es:** Fecha y hora de la ultima modificacion del registro, con formato `yyyyMMddHHmmss.f`.

**Para que sirve:** Permite a sistemas externos (via OAI-PMH u otros protocolos) detectar que registros cambiaron desde la ultima sincronizacion. Es esencial para cosecha incremental.

**Impacto tecnico:** Bajo. Se puede generar directamente desde `updated_at` del libro/articulo.

**Consultar al cliente:** Si planean integrarse con un catalogo colectivo o usar OAI-PMH.

---

### 4.2 Campo 040 — Fuente de catalogacion

**Que es:** Identifica la institucion que creo, modifico y/o transcribio el registro.

**Subcampos:**
- `$a` Agencia catalogadora original
- `$b` Idioma de catalogacion
- `$c` Agencia transcriptora

**Para que sirve:** Obligatorio en catalogos colectivos para saber quien catalogo cada registro. Permite trazabilidad institucional.

**Impacto tecnico:** Bajo. Valores fijos (ej: `$a AR-BuSIGB $b spa $c AR-BuSIGB`).

**Consultar al cliente:** Si participan en algun catalogo colectivo o red de bibliotecas.

---

### 4.3 Campos 082/084 — Clasificacion Dewey u otra

**Que es:**
- `082`: Clasificacion Decimal Dewey (CDD)
- `084`: Otros sistemas de clasificacion

**Para que sirve:** Actualmente exportamos CDU en campo 080. Si la biblioteca tambien usa Dewey o algun otro sistema, habria que agregar estos campos.

**Impacto tecnico:** Medio. Requiere agregar un campo al modelo `Libro` si usan un sistema adicional al CDU.

**Consultar al cliente:** Que sistema(s) de clasificacion utilizan (CDU, Dewey, ambos, otro).

---

### 4.4 Control de autoridades ($0 en campos 100/700)

**Que es:** El subcampo `$0` en campos de autor (100, 700) enlaza la persona a un registro de autoridad externo, tipicamente VIAF (Virtual International Authority File) u otro catalogo de autoridades.

**Ejemplo:**
```xml
<datafield tag="100" ind1="1" ind2=" ">
  <subfield code="a">Cormen, Thomas H.</subfield>
  <subfield code="0">http://viaf.org/viaf/34488559</subfield>
</datafield>
```

**Para que sirve:** Desambigua autores con nombres similares. Permite interoperabilidad con catalogos internacionales. Es el estandar de oro para catalogos profesionales.

**Impacto tecnico:** Alto. Requiere:
- Agregar campo `viaf_id` (o similar) a la tabla `persona`
- Mecanismo de busqueda/vinculacion con VIAF (manual o automatico)
- Interfaz para que el catalogador asocie personas con registros de autoridad

**Consultar al cliente:** Si necesitan control de autoridades y con que fuente (VIAF, catalogo nacional, etc).

---

## 5. Referencias

- [Referencia tecnica de implementacion](marc21-impl.md) — mapeo completo, endpoints, formatos
- [Library of Congress MARC21 Bibliographic](https://www.loc.gov/marc/bibliographic/) — especificacion oficial
- [MARC Country Codes](https://www.loc.gov/marc/countries/) — codigos de pais
- [ISO 639-2 Language Codes](https://www.loc.gov/standards/iso639-2/php/code_list.php) — codigos de idioma
