# ADR-002: Estrategia de multi-institución

**Estado:** Aceptado
**Fecha:** 2026-09-10
**Decide:** davinic7 (responsable único del proyecto)

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

Opción A: base compartida con `institucion_id`, con el aislamiento aplicado
mediante *global scopes* de Eloquent en todos los modelos de negocio.

## Consecuencias

- Se vuelve más fácil: una sola base para operar y migrar; el filtro por
  institución queda resuelto en un solo lugar por modelo (el scope), no
  repetido a mano en cada consulta.
- Se vuelve más difícil: **no se puede restaurar el backup de una sola
  institución sin trabajo manual** — el backup es de toda la base, y
  extraer los datos de una institución puntual desde ese volcado es un
  proceso ad hoc, no un restore directo.
- Mitigación de ese riesgo:
  - Baja lógica en todo (`deleted_at`); nunca borrado físico de registros
    de negocio.
  - Exportación por institución (dump filtrado por `institucion_id`) antes
    de cualquier operación riesgosa sobre esa institución — migración de
    datos, borrado masivo, cambio de plan, etc.
- Habrá que revisar más adelante: si una institución pide irse del sistema
  o requiere aislamiento físico real (por ejemplo, por exigencia legal
  propia), evaluar si conviene migrarla a una base separada en ese momento
  puntual, en vez de rediseñar el esquema general.
