# Instructivo: Gestión de Libros — API SIGB

Base URL: `http://localhost:8080/api/v1`

Todos los endpoints requieren autenticación Bearer JWT, excepto login/register.

---

## Modelo de datos

Un **libro** se compone de dos capas:

- **Artículo** (`articulos`): datos bibliográficos comunes a cualquier tipo de material (título, año, idioma, temas). El `tipo` es asignado automáticamente por el servidor según el endpoint utilizado — para `/libros` siempre será `"libro"`.
- **Libro** (`libros`): datos específicos del soporte físico (ISBN, páginas, editorial, personas, etc.).

Ambas capas se crean y devuelven juntas a través de los endpoints de `/libros`.

---

## 1. Crear un libro — `POST /libros`

El body es un JSON con dos objetos: `articulo` y `libro`. Artículo y libro se crean en una sola transacción. El campo `tipo` **no se envía** — el servidor lo establece automáticamente como `"libro"`.

### Campos de `articulo`

| Campo              | Tipo   | Requerido | Restricciones                                              |
|--------------------|--------|-----------|------------------------------------------------------------|
| `titulo`           | string | ✅        | No vacío, max 100 chars                                    |
| `anio_publicacion` | int    | ✅        | Entre 1000 y año actual                                    |
| `idioma`           | string | ❌        | Código ISO 639-1 de 2 chars (`"es"`, `"en"`, `"pt"`, `"fr"`, `"de"`, `"it"`). Default: `"es"` |
| `descripcion`      | string | ❌        | Max 255 chars                                              |

### Campos de `libro`

| Campo                  | Tipo   | Requerido | Restricciones                                                              |
|------------------------|--------|-----------|----------------------------------------------------------------------------|
| `isbn`                 | string | ❌        | 10 o 13 dígitos. Excluyente con `issn`. Único en el sistema.               |
| `issn`                 | string | ❌        | Formato `XXXX-XXXX`. Excluyente con `isbn`. Único en el sistema.           |
| `paginas`              | int    | ❌        | Entero positivo (> 0)                                                      |
| `titulo_informativo`   | string | ❌        | Max 255 chars                                                              |
| `cdu`                  | int    | ❌        | Entero entre 0 y 999 (clase principal CDU)                                 |
| `editorial`            | string | ❌        | Max 200 chars                                                              |
| `lugar_de_publicacion` | string | ❌        | Max 200 chars                                                              |
| `edicion`              | string | ❌        | Max 100 chars                                                              |
| `dimensiones`          | string | ❌        | Max 50 chars (ej: `"21x15 cm"`)                                            |
| `ilustraciones`        | string | ❌        | Max 100 chars (ej: `"blanco y negro"`, `"color"`)                          |
| `serie`                | string | ❌        | Max 255 chars                                                              |
| `numero_serie`         | string | ❌        | Max 50 chars                                                               |
| `notas`                | string | ❌        | Sin límite de longitud                                                     |
| `pais_publicacion`     | string | ❌        | Código ISO 3166-1 alpha-2 de exactamente 2 chars (ej: `"AR"`, `"US"`, `"ES"`) |
| `personas`             | array  | ❌        | Ver detalle abajo. Default: `[]`                                           |

### Detalle de `personas`

Array de objetos. Cada objeto representa una persona vinculada al libro.

| Campo      | Tipo   | Requerido | Restricciones                                                                  |
|------------|--------|-----------|--------------------------------------------------------------------------------|
| `nombre`   | string | ✅        | No vacío, max 100 chars                                                        |
| `apellido` | string | ✅        | No vacío, max 100 chars                                                        |
| `rol`      | string | ✅        | Uno de: `autor`, `coautor`, `colaborador`, `editor`, `traductor`, `ilustrador` |
| `orden`    | int    | ❌        | Posición en la ficha bibliográfica. Default: índice en el array (0, 1, 2...)   |

**Comportamiento de personas:**
- Si ya existe una persona con el mismo `nombre` + `apellido`, se reutiliza (no se duplica).
- Si no existe, se crea automáticamente.
- La misma persona puede tener distintos roles en distintos libros.

### Ejemplo — request

```json
POST /api/v1/libros
Authorization: Bearer <token>
Content-Type: application/json

{
  "articulo": {
    "titulo": "Algorithms",
    "anio_publicacion": 2011,
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
      { "nombre": "Robert", "apellido": "Sedgewick", "rol": "autor",   "orden": 0 },
      { "nombre": "Kevin",  "apellido": "Wayne",     "rol": "coautor", "orden": 1 }
    ]
  }
}
```

### Ejemplo — response `201 Created`

```json
{
  "data": {
    "id": 42,
    "isbn": "9780321573513",
    "issn": null,
    "paginas": 955,
    "titulo_informativo": "Algorithms, 4th Edition",
    "cdu": 519,
    "editorial": "Addison-Wesley",
    "lugar_de_publicacion": "New Jersey",
    "edicion": "4ta edición",
    "dimensiones": "24x18 cm",
    "ilustraciones": null,
    "serie": "Computer Science Series",
    "numero_serie": null,
    "notas": null,
    "pais_publicacion": "US",
    "personas": [
      { "nombre": "Robert", "apellido": "Sedgewick", "rol": "autor",   "orden": 0 },
      { "nombre": "Kevin",  "apellido": "Wayne",     "rol": "coautor", "orden": 1 }
    ],
    "articulo": {
      "titulo": "Algorithms",
      "anio_publicacion": 2011,
      "tipo": "libro",
      "idioma": "en",
      "descripcion": "Comprehensive introduction to algorithms and data structures.",
      "temas": []
    }
  },
  "message": "Libro creado exitosamente"
}
```

> El campo `tipo` aparece en la **respuesta** como `"libro"`, pero **no se envía en la request**.

---

## 2. Obtener un libro — `GET /libros/{id}`

Devuelve el libro con su artículo y personas asociadas.

```
GET /api/v1/libros/42
Authorization: Bearer <token>
```

Response `200 OK`: mismo shape que el `data` del ejemplo de creación.

---

## 3. Buscar libros — `GET /libros`

Listado paginado con filtros opcionales.

### Parámetros de query

| Parámetro              | Tipo   | Default   | Descripción                              |
|------------------------|--------|-----------|------------------------------------------|
| `page`                 | int    | `1`       | Número de página                         |
| `per_page`             | int    | `10`      | Resultados por página (máx. 100)         |
| `sort_by`              | string | `titulo`  | Campo de ordenamiento (`titulo`, `anio_publicacion`, `editorial`, `isbn`, `idioma`, `id`) |
| `sort_dir`             | string | `asc`     | `asc` o `desc`                           |
| `titulo`               | string | —         | Filtro parcial por título                |
| `isbn`                 | string | —         | Filtro exacto por ISBN                   |
| `editorial`            | string | —         | Filtro parcial por editorial             |
| `idioma`               | string | —         | Filtro exacto por idioma (`es`, `en`...) |

---

## 4. Actualizar datos del libro — `PATCH /libros/{id}`

Actualiza **solo los campos de libro** enviados. Los campos ausentes no se modifican.

### Restricciones

- `isbn` e `issn` **no son modificables** vía PATCH. Si se envían, el sistema responde `400`.
- Si se envía `personas`, **reemplaza completamente** la lista anterior (sync total, no aditivo).
- Si no se envía `personas`, las personas existentes no se modifican.

### Campos modificables

Todos los campos de `libro` de la tabla de creación, **excepto** `isbn` e `issn`.

### Ejemplo — cambiar editorial y agregar notas

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

> Enviar `personas` reemplaza TODA la lista. Si se omite a Sedgewick, queda desvinculado del libro.

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

Response `200 OK`:

```json
{
  "data": { ...libro actualizado completo... },
  "message": "Libro actualizado exitosamente"
}
```

---

## 5. Actualizar datos del artículo — `PATCH /articulos/{id}`

Los campos bibliográficos del artículo (título, año, idioma, descripción) se actualizan por un endpoint separado.

### Campos modificables

| Campo              | Tipo   | Restricciones                                                      |
|--------------------|--------|--------------------------------------------------------------------|
| `titulo`           | string | No vacío, max 100 chars                                            |
| `anio_publicacion` | int    | Entre 1000 y año actual                                            |
| `tipo`             | string | Uno de: `libro`, `revista`, `tesis`, `mapa`, `partitura`          |
| `idioma`           | string | Código ISO 639-1 de 2 chars                                        |
| `descripcion`      | string | Max 255 chars. Enviar `null` para borrar el valor.                 |

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

## 6. Eliminar un libro — `DELETE /libros/{id}`

Elimina el libro y su artículo asociado en cascada.

```
DELETE /api/v1/libros/42
Authorization: Bearer <token>
```

Response `200 OK`:

```json
{ "message": "Libro eliminado exitosamente" }
```

---

## 7. Gestión de temas

Los temas se gestionan con endpoints propios; **no se envían en el create ni en el PATCH del libro**.

```
POST   /api/v1/articulos/{idArticulo}/temas/{idTema}   — asocia un tema existente
DELETE /api/v1/articulos/{idArticulo}/temas/{idTema}   — desasocia un tema
GET    /api/v1/articulos/{idArticulo}/temas             — lista temas del artículo
```

**Errores posibles:**
- `404` si el artículo o el tema no existe.
- `409` si el tema ya está asociado (POST) o no estaba asociado (DELETE).

---

## 8. Resumen de errores comunes

| Situación                                          | HTTP | Descripción                                                        |
|----------------------------------------------------|------|--------------------------------------------------------------------|
| ISBN y ISSN enviados juntos                        | 400  | Un libro no puede tener ISBN y ISSN a la vez                       |
| ISBN o ISSN ya registrado en otro libro            | 409  | Libro ya existe                                                    |
| Se intenta modificar ISBN o ISSN vía PATCH         | 400  | El ISBN/ISSN no puede ser modificado                               |
| Rol de persona inválido                            | 400  | El campo rol debe ser uno de: autor, coautor, colaborador, editor, traductor, ilustrador |
| CDU fuera de rango                                 | 400  | El campo cdu debe estar entre 0 y 999                              |
| Persona con typo en nombre                         | —    | Se crea una persona nueva. Corregir vía PATCH enviando la lista completa con el nombre correcto |
