# **Normas de Desarrollo y Convenciones del Proyecto**

Sistema Integrado de Gestión Bibliotecaria (SIGB)

## **1\. Propósito del documento**

Documentar procesos, reglas, validaciones y convenciones para el SIGB Cresta 

---

## **2\. Idioma del proyecto**

### **2.1 Código fuente**

Idioma principal del código: INGLÉS  
Variables, funciones, clases y archivos deben nombrarse en inglés.  
Se prioriza claridad por sobre brevedad.

### **2.2 Comentarios y documentación técnica**

Comentarios técnicos en español.  
Se recomienda explicar el "por qué" de una decisión y no describir código evidente.

### **2.3 Interfaz de usuario**

Todos los textos visibles al usuario final deben estar en español.  
 Mensajes claros, breves y orientados al usuario de biblioteca.

---

## **3\. Convenciones de nombrado**

### **3.1 Variables**

Formato: camelCase  
Nombres descriptivos y semánticos.  
Evitar abreviaturas, siglas ambiguas o nombres genéricos.

Ejemplos:

* userId

* bookTitle

* loanDate

* isAvailable

---

### **3.2 Constantes**

Formato: UPPER\_SNAKE\_CASE  
Se utilizan únicamente para valores fijos del sistema.

Ejemplos:

* MAX\_LOAN\_DAYS

* DEFAULT\_USER\_ROLE

* FINE\_PER\_DAY

---

### **3.3 Funciones y métodos**

Formato: camelCase  
Deben comenzar con un verbo en inglés.  
El nombre debe expresar claramente la acción que realiza.

Ejemplos:

* createUser()

* updateBook()

* calculateFine()

* validateLoan()

* findAvailableCopies()

---

### **3.4 Clases**

Formato: PascalCase  
Sustantivos en singular.  
Representan entidades del dominio o servicios del sistema.

Ejemplos:

* User

* Book

* Loan

* Reservation

* UserService

* LoanRepository

---

### **3.5 Interfaces**

Formato: IPascalCase  
Se utiliza I como prefijo  
El nombre debe expresar el rol o responsabilidad.

Ejemplos:

* IUserRepository

* ILoanServiceInterface

---

### **3.6 Archivos y carpetas**

Carpetas: kebab-case  
Archivos de clases: PascalCase con extensión correspondiente.

---

## **4\. Convenciones de base de datos**

### **4.1 Tablas**

 Formato: snake\_case en singular.  
 Los nombres deben representar claramente la entidad almacenada.

Ejemplos:

* user

* book

* loan

* book\_copy

---

### **4.2 Columnas**

Formato: snake\_case.  
Evitar abreviaturas poco claras.

Ejemplos:

* user\_id

* created\_at

* due\_date

* is\_active

---

### **4.3 Claves primarias y foráneas**

Clave primaria: id  
Claves foráneas: \<entity\>\_id

Ejemplos:

* id

* book\_id

* user\_id

---

## **5\. Convenciones de API y backend**

### **5.1 Diseño de endpoints**

Estilo REST.  
Recursos en plural.  
No usar verbos en la URL.  
Uso correcto de métodos HTTP.

Ejemplos:

* GET /users

* POST /loans

* PUT /books/{id}

* DELETE /reservations/{id}

---

### **5.2 Respuestas HTTP**

Uso adecuado de códigos de estado HTTP.  
Las respuestas deben ser consistentes en estructura.

Ejemplos:

* 200 OK

* 201 Created

* 400 Bad Request

* 401 Unauthorized

* 404 Not Found

* 500 Internal Server Error

---

## **6\. Manejo de errores y validaciones**

Validaciones del lado servidor obligatorias.  
No exponer información sensible en mensajes de error.  
Los logs detallados se mantienen del lado del servidor.

Ejemplo de respuesta:  
 {  
 "error": true,  
 "message": "Book not available for loan"  
 }

---

## **7\. Comentarios y documentación interna**

Usar comentarios solo cuando aporten valor.  
Evitar comentar código autoexplicativo.  
Documentar funciones públicas con bloques de documentación.

---

## **8\. Control de versiones**

### **8.1 Commits**

Formato:  
tipo: descripción corta y clara

Tipos sugeridos:

* feat: nueva funcionalidad

* fix: corrección de errores

* refactor: refactor interno

* docs: documentación

* test: pruebas

* chore: tareas generales

---

### **8.2 Ramas**

main: rama estable | Tendra CI  
develop: rama de integración  
feature/\<nombre\>: nuevas funcionalidades  
fix/\<nombre\>: correcciones

---

## **9\. Buenas prácticas generales**

Funciones pequeñas y con una sola responsabilidad.  
Separación clara entre capas (controlador, servicio, repositorio).  
Priorizar legibilidad y mantenibilidad.  
Código claro \> código ingenioso.

---

## **10\. Mantenimiento de la wiki**

Este documento forma parte de la wiki del proyecto.  
Debe mantenerse actualizado.  
Las modificaciones relevantes deben registrarse indicando fecha y motivo del cambio.

