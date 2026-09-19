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

### Cómo se resuelve la institución activa en cada request

La institución activa es un único valor por sesión, `institucion_id`, que
lee `App\Support\InstitucionContext`. Es la misma noción para los datos
(`InstitucionScope`) y para los permisos (spatie/laravel-permission con
*teams*, vía `InstitucionTeamResolver`): no hay dos fuentes que puedan
desincronizarse.

El middleware `EstablecerInstitucionActiva` corre en cada request web de un
usuario autenticado y **revalida** el valor de la sesión:

1. Si la institución de la sesión sigue entre las que el usuario puede usar,
   se conserva.
2. Si no (nunca se eligió, se dio de baja la institución o cambió la
   matrícula del usuario), se descarta. Si el usuario puede usar
   exactamente una, se fija esa.
3. Con varias y ninguna elegida queda sin institución. Ese estado es
   *fail-closed*: `InstitucionScope` no devuelve ninguna fila hasta que el
   usuario elige una en el selector de la barra lateral.

Qué instituciones puede usar un usuario lo define
`User::institucionesAccesibles()`: todas para el superadmin y, para el
resto, solo aquellas en las que está matriculado (`institucion_user`). El
bypass de Gate del superadmin salta las autorizaciones, pero no este
filtrado: un superadmin también debe tener una institución activa para ver
datos de negocio.

Como el valor se revalida en cada request, manipular la sesión no da acceso
a datos de otra institución, y las bajas o cambios de matrícula se reflejan
de inmediato. El costo es una o dos consultas extra por request.

## Consecuencias

- Se vuelve más fácil: una sola base para operar y migrar; el filtro por
  institución queda resuelto en un solo lugar por modelo (el scope), no
  repetido a mano en cada consulta.
- Impacto en migraciones: cada migración se aplica una sola vez y afecta a
  todas las instituciones a la vez, así que no hay que orquestar una
  corrida por base. Toda tabla de negocio nueva lleva `institucion_id`
  (indexado); al agregar esa columna a una tabla que ya tiene datos, la
  migración debe completar el valor de las filas existentes antes de
  volverla obligatoria.
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
