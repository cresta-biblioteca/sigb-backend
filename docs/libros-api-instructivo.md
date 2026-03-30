# Instructivo: Gestión de Libros — API SIGB

Base URL: `http://localhost:8080/api/v1`

Todos los endpoints requieren autenticación Bearer JWT, excepto login/register.

---

## 1. Crear un libro — `POST /libros`

El body es un JSON con dos objetos: `articulo` (datos bibliográficos generales) y `libro` (datos específicos del libro). Artículo y libro se crean en una sola transacción.

### Campos de `articulo`

| Campo             | Tipo    | Requerido | Restricciones                        |
|-------------------|---------|-----------|--------------------------------------|
| `titulo`          | string  | ✅        | No vacío, max 100 chars              |
| `anio_publicacion`| int     | ✅        | Entre 1000 y año actual              |
| `tipo_documento_id`| int    | ✅        | ID existente en tabla tipo_documento |
| `idioma`          | string  | ❌        | Exactamente 2 chars (ej: "es", "en"). Default: `"es"` |
| `descripcion`     | string  | ❌        | Max 255 chars                        |

### Campos de `libro`

| Campo                  | Tipo    | Requerido | Restricciones                                              |
|------------------------|---------|-----------|------------------------------------------------------------|
| `isbn`                 | string  | ❌        | 10 o 13 dígitos. Excluyente con `issn`. Único en el sistema. |
| `issn`                 | string  | ❌        | Formato `XXXX-XXXX`. Excluyente con `isbn`. Único en el sistema. |
| `paginas`              | int     | ❌        | Entero positivo (> 0)                                      |
| `titulo_informativo`   | string  | ❌        | Max 255 chars                                              |
| `cdu`                  | int     | ❌        | Entero entre 0 y 999 (clase principal CDU)                 |
| `editorial`            | string  | ❌        | Max 200 chars                                              |
| `lugar_de_publicacion` | string  | ❌        | Max 200 chars                                              |
| `edicion`              | string  | ❌        | Max 100 chars                                              |
| `dimensiones`          | string  | ❌        | Max 50 chars (ej: "21x15 cm")                              |
| `ilustraciones`        | string  | ❌        | Max 100 chars (ej: "blanco y negro", "color")              |
| `serie`                | string  | ❌        | Max 255 chars                                              |
| `numero_serie`         | string  | ❌        | Max 50 chars                                               |
| `notas`                | string  | ❌        | Sin límite de longitud                                     |
| `pais_publicacion`     | string  | ❌        | Exactamente 2 chars, código ISO 3166-1 (ej: "AR", "ES")    |
| `personas`             | array   | ❌        | Ver detalle abajo. Default: `[]`                           |

### Detalle de `personas`

Array de objetos. Cada objeto representa una persona vinculada al libro (autor, colaborador, etc.).

| Campo      | Tipo   | Requerido | Restricciones                                                          |
|------------|--------|-----------|------------------------------------------------------------------------|
| `nombre`   | string | ✅        | No vacío, max 100 chars                                                |
| `apellido` | string | ✅        | No vacío, max 100 chars                                                |
| `rol`      | string | ✅        | Uno de: `autor`, `coautor`, `colaborador`, `editor`, `traductor`, `ilustrador` |
| `orden`    | int    | ❌        | Posición en la ficha bibliográfica. Default: índice en el array (0, 1, 2...) |

**Comportamiento de personas:**
- Si ya existe una persona con el mismo `nombre` + `apellido`, se reutiliza (no se duplica).
- Si no existe, se crea automáticamente.
- El mismo `nombre` + `apellido` puede tener distintos roles en distintos libros.

### Ejemplo completo — request

```json
POST /api/v1/libros
Authorization: Bearer <token>
Content-Type: application/json

{
  "articulo": {
    "titulo": "Algorithms",
    "anio_publicacion": 2011,
    "tipo_documento_id": 1,
    "idioma": "en",
    "descripcion": "Comprehensive introduction to algorithms and data structures."
  },
  "libro": {
    "isbn": "9780321573513",
    "paginas": 955,
    "titulo_informativo": "Algorithms, 4th Edition",
    "cdu": 519,
    "editorial": "Addison-Wesley",
    "lugar_de_publicacion": "New Jersey",
    "pais_publicacion": "US",
    "edicion": "4ta edición",
    "dimensiones": "24x18 cm",
    "serie": "Computer Science Series",
    "personas": [
      { "nombre": "Robert",  "apellido": "Sedgewick", "rol": "autor",   "orden": 0 },
      { "nombre": "Kevin",   "apellido": "Wayne",     "rol": "coautor", "orden": 1 }
    ]
  }
}
```

### Ejemplo completo — response `201 Created`

```json
{
  "data": {
    "id": 42,
    "titulo": "Algorithms",
    "anio_publicacion": 2011,
    "tipo_documento_id": 1,
    "idioma": "en",
    "descripcion": "Comprehensive introduction to algorithms and data structures.",
    "isbn": "9780321573513",
    "paginas": 955,
    "titulo_informativo": "Algorithms, 4th Edition",
    "cdu": 519,
    "editorial": "Addison-Wesley",
    "lugar_de_publicacion": "New Jersey",
    "pais_publicacion": "US",
    "edicion": "4ta edición",
    "dimensiones": "24x18 cm",
    "serie": "Computer Science Series",
    "numero_serie": null,
    "issn": null,
    "ilustraciones": null,
    "notas": null,
    "personas": [
      { "id": 1, "nombre": "Robert",  "apellido": "Sedgewick", "rol": "autor",   "orden": 0 },
      { "id": 2, "nombre": "Kevin",   "apellido": "Wayne",     "rol": "coautor", "orden": 1 }
    ]
  },
  "message": "Libro creado exitosamente"
}
```

---

## 2. Actualizar un libro — `PATCH /libros/{id}`

Actualiza **solo los campos de libro** que se envíen. Los campos no incluidos en el body **no se modifican**.

### Restricciones del PATCH libro

- `isbn` e `issn` **no son modificables**. Si se envían, el sistema responde con error `400`.
- Si se envía `personas`, **reemplaza completamente** la lista anterior (sync total). No es aditivo.
- Si no se envía `personas`, las personas existentes **no se tocan**.

### Campos modificables

Todos los campos de `libro` listados en la tabla de creación, **excepto** `isbn` e `issn`.

### Ejemplo — agregar notas y cambiar editorial

```json
PATCH /api/v1/libros/42
Authorization: Bearer <token>
Content-Type: application/json

{
  "editorial": "Pearson Education",
  "notas": "Incluye acceso a sitio web con ejercicios interactivos."
}
```

### Ejemplo — reemplazar lista de personas

> ⚠️ Enviar `personas` reemplaza TODA la lista. Si se omite a Sedgewick, queda desvinculado del libro.

```json
PATCH /api/v1/libros/42
Authorization: Bearer <token>
Content-Type: application/json

{
  "personas": [
    { "nombre": "Robert", "apellido": "Sedgewick", "rol": "autor",   "orden": 0 },
    { "nombre": "Kevin",  "apellido": "Wayne",     "rol": "coautor", "orden": 1 },
    { "nombre": "Jon",    "apellido": "Bentley",   "rol": "editor",  "orden": 2 }
  ]
}
```

### Ejemplo — response `200 OK`

```json
{
  "data": { ... libro actualizado completo ... },
  "message": "Libro actualizado exitosamente"
}
```

---

## 3. Actualizar datos del artículo — `PATCH /articulos/{id}`

Los campos de `articulo` (título, año, idioma, etc.) se actualizan por un endpoint separado.

### Campos modificables

| Campo              | Tipo   | Restricciones                                                                 |
|--------------------|--------|-------------------------------------------------------------------------------|
| `titulo`           | string | No vacío, max 100 chars                                                       |
| `anio_publicacion` | int    | Entre 1000 y año actual                                                       |
| `tipo_documento_id`| int    | ⚠️ No modificable si el artículo está asociado a un libro                    |
| `idioma`           | string | Exactamente 2 chars                                                           |
| `descripcion`      | string | Max 255 chars. Puede enviarse `null` para borrar el valor.                    |

### Ejemplo

```json
PATCH /api/v1/articulos/42
Authorization: Bearer <token>
Content-Type: application/json

{
  "titulo": "Algorithms, Fourth Edition",
  "descripcion": null
}
```

---

## 4. Asociar temas y materias

Temas y materias **no se envían en el create ni en el PATCH**. Se gestionan con endpoints propios usando IDs previamente existentes.

### Temas

```
POST   /api/v1/articulos/{idArticulo}/temas/{idTema}    — asocia un tema
DELETE /api/v1/articulos/{idArticulo}/temas/{idTema}    — desasocia un tema
GET    /api/v1/articulos/{idArticulo}/temas             — lista temas del artículo
```

### Materias

```
POST   /api/v1/articulos/{idArticulo}/materias/{idMateria}    — asocia una materia
DELETE /api/v1/articulos/{idArticulo}/materias/{idMateria}    — desasocia una materia
GET    /api/v1/articulos/{idArticulo}/materias               — lista materias del artículo
```

**Errores posibles:**
- `404` si el artículo o el tema/materia no existe.
- `409` si el tema/materia ya está asociado (en POST) o ya fue eliminado (en DELETE).

---

## 5. Resumen de errores comunes

| Situación | HTTP | Mensaje |
|-----------|------|---------|
| ISBN y ISSN enviados juntos | 400 | "Un libro no puede tener ISBN y ISSN a la vez" |
| ISBN ya registrado en otro libro | 409 | LibroAlreadyExistsException |
| Se intenta modificar ISBN/ISSN vía PATCH | 400 | "El ISBN/ISSN no puede ser modificado" |
| Se intenta cambiar tipo_documento_id en artículo con libro | 422 | "No se puede modificar tipo_documento_id porque el artículo está asociado a un libro" |
| Rol de persona inválido | 400 | "El campo rol debe ser uno de: autor, coautor, colaborador, editor, traductor, ilustrador" |
| CDU fuera de rango | 400 | "El campo cdu debe estar entre 0 y 999" |
| Persona con typo en nombre | — | Se crea una persona nueva. Corregir vía PATCH enviando la lista completa con el nombre correcto. |
