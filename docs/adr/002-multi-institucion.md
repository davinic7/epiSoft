# ADR-002: Estrategia de multi-institución

**Estado:** Propuesto
**Fecha:**
**Decide:**

## Contexto

Una sola instalación da servicio a varias EPI. Los datos de una institución
no pueden ser accesibles desde otra bajo ninguna circunstancia, incluida la
manipulación de identificadores en la URL. Hay usuarios que pueden pertenecer
a más de una institución con roles distintos en cada una.

Con el stack definido en [ADR-001](001-stack.md) (Laravel + Eloquent),
Laravel ofrece *global scopes*: un mecanismo del propio ORM para aplicar un
filtro por `institucion_id` a toda consulta de forma automática, sin
depender de que cada desarrollador lo repita a mano en cada query. Esto
inclina la balanza hacia la Opción A frente a lo que costaría igual
aislamiento con un stack sin ese mecanismo.

## Opciones consideradas

### Opción A: base compartida con `institucion_id`

| Dimensión | Evaluación |
|---|---|
| Complejidad | Media |
| Costo | Bajo, una sola base |
| Riesgo | Una consulta sin filtrar expone datos de otra institución |
| Backups | Un solo backup, pero no se restaura una institución sola |

### Opción B: una base por institución

| Dimensión | Evaluación |
|---|---|
| Complejidad | Alta, conexión dinámica y migraciones por base |
| Costo | Mayor, según el hosting |
| Riesgo | Aislamiento garantizado por diseño |
| Backups | Restauración individual posible |

## Decisión

Pendiente.

## Consecuencias

Pendiente.
