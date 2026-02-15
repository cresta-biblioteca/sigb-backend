# ADR-001: Arquitectura de Monolito Modular con MVC

## Estado

Aceptada

## Fecha

2026-02-15

## Contexto

SIGB (Sistema Integrado de Gestión Bibliotecaria) es un sistema de gestión para bibliotecas que debe manejar:

- Autenticación y autorización de usuarios
- Catálogo bibliográfico (artículos, libros, ejemplares)
- Gestión de lectores/socios
- Circulación (préstamos, reservas, devoluciones)

El proyecto es desarrollado como práctica profesional estudiantil, lo que implica:

- Equipo pequeño con experiencia limitada en PHP
- Necesidad de estructura clara y mantenible
- Tiempo acotado de desarrollo
- Posibilidad de que otros estudiantes continúen el proyecto

Se evaluaron las siguientes alternativas:

1. **Monolito tradicional**: Una estructura plana sin separación de dominios
2. **Monolito modular**: Separación por bounded contexts con MVC interno
3. **Microservicios**: Servicios independientes por dominio

## Decisión

Adoptamos una **arquitectura de monolito modular** con las siguientes características:

### Estructura de Módulos

```
src/
├── Auth/                    # Autenticación y autorización
├── Catalogo/                # Inventario bibliográfico
│   ├── Articulos/          # Entidad base del catálogo
│   ├── Libros/             # Especialización de artículo
│   ├── Ejemplares/         # Copias físicas
│   └── Shared/             # Código compartido del módulo
├── Lectores/                # Gestión de socios
├── Circulacion/             # Préstamos y reservas
└── Shared/                  # Código compartido global
```

### Patrón Interno: MVC por Módulo

Cada módulo sigue el patrón MVC:

```
Modulo/
├── Controllers/    # Manejo de requests HTTP
├── Models/         # Entidades y acceso a datos
└── Services/       # Lógica de negocio
```

### Mapeo de Entidades a Módulos

| Módulo | Entidades |
|--------|-----------|
| Auth | User, Role, Permiso |
| Catalogo/Articulos | Articulo, TipoDocumento, Tema, Materia |
| Catalogo/Libros | Libro |
| Catalogo/Ejemplares | Ejemplar |
| Lectores | Lector, Carrera |
| Circulacion | Prestamo, Reserva, TipoPrestamo |

### Reglas de Dependencia

Las dependencias entre módulos son **unidireccionales**:

```
Auth ←────── Lectores
                ↓
Catalogo ←─── Circulacion
                ↑
             Lectores
```

- `Circulacion` puede importar de `Catalogo` y `Lectores`
- `Lectores` puede importar de `Auth`
- `Catalogo` NO debe importar de `Circulacion` ni `Lectores`
- `Auth` NO debe importar de ningún otro módulo de dominio

### Comunicación entre Módulos

Los módulos se comunican mediante:

1. **Importación directa de Models** (para consultas simples)
2. **Services** (para operaciones complejas que cruzan dominios)

No se utilizan eventos ni mensajería asíncrona para mantener la simplicidad.

## Consecuencias

### Positivas

- **Separación clara de responsabilidades**: Cada módulo tiene un propósito definido
- **Facilidad de navegación**: Estructura predecible para nuevos desarrolladores
- **Escalabilidad del equipo**: Diferentes personas pueden trabajar en módulos distintos
- **Testeo aislado**: Cada módulo puede testearse de forma independiente
- **Camino a microservicios**: Si en el futuro se requiere, los módulos pueden extraerse como servicios
- **Bajo overhead**: Sin complejidad de comunicación entre servicios distribuidos

### Negativas

- **Disciplina requerida**: El equipo debe respetar las reglas de dependencia manualmente
- **Sin aislamiento real**: Un error en un módulo puede afectar a todo el sistema
- **Base de datos compartida**: No hay aislamiento de datos por módulo
- **Posible acoplamiento**: Sin herramientas que fuercen los límites, es fácil violar las reglas

### Riesgos y Mitigaciones

| Riesgo | Mitigación |
|--------|------------|
| Violación de dependencias | Code review enfocado en imports entre módulos |
| Código duplicado entre módulos | Carpeta `Shared/` para código común |
| Módulos demasiado grandes | Subdivisión como en Catalogo (Articulos, Libros, Ejemplares) |

## Alternativas Descartadas

### Monolito Tradicional

Estructura plana (`src/Controllers/`, `src/Models/`, etc.) sin separación por dominio.

**Descartada porque**: Dificulta la navegación a medida que crece el proyecto y no prepara para una eventual modularización.

### Microservicios

Servicios independientes desplegados por separado.

**Descartada porque**:
- Overhead operacional excesivo para el alcance del proyecto
- Complejidad innecesaria para un equipo pequeño
- El dominio no justifica distribución (operaciones transaccionales entre préstamos, ejemplares y lectores)

## Referencias

- Fowler, M. "Monolith First" - https://martinfowler.com/bliki/MonolithFirst.html
- Vernon, V. "Implementing Domain-Driven Design" - Capítulo sobre Bounded Contexts
- PHP Framework Interop Group - PSR-4 Autoloading Standard
