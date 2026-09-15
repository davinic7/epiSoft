# ADR-003: Equipos técnicos provinciales

**Estado:** Aceptado
**Fecha:** 2026-09-14
**Decide:** davinic7 (responsable único del proyecto)

## Contexto

El capítulo 2 de la guía de buenas prácticas (docs/guia-buenas-practicas-epi.md)
distingue con claridad dos categorías de personal:

- **Actores institucionales**: trabajan dentro de una única EPI (equipo de
  coordinación, encargada/o de recepción, coordinador/a pedagógico/a,
  educadoras/es, encargado/a de economato, personal de cocina, personal de
  mantenimiento y limpieza). Se modelan como `RolInstitucional`.
- **Equipos técnicos itinerantes de la Dirección Provincial de Primera
  Infancia** (abordaje global, nutrición, trabajo social): "responsables de
  realizar recorridos institucionales por **todos los espacios**,
  acompañando dinámicas de funcionamiento general y situaciones
  particulares" (cap. 2, pág. 15). No dependen de ninguna EPI puntual, sino
  de la Dirección Provincial, y su trabajo es explícitamente transversal:
  intervienen en el abordaje de vulneración de derechos (cap. 6.1),
  desafíos en el desarrollo (cap. 6.2), el circuito de aprobación del menú
  semanal (docs/backlog.txt, issue "Menú semanal") y las articulaciones
  interinstitucionales (cap. 2, pág. 17).

Esto entra en tensión con dos decisiones ya tomadas:

1. `RolInstitucional` (este PR) modela "un rol por institución": un usuario
   puede tener un rol distinto en cada institución a la que pertenece, pero
   el supuesto implícito hasta ahora era que cada persona trabaja
   "de planta" en una o pocas EPI conocidas de antemano.
2. `InstitucionContext` (ADR-002) resuelve una **única** institución activa
   por sesión, y de ella depende tanto el aislamiento de datos
   (`InstitucionScope`) como la resolución de permisos de spatie
   (`InstitucionTeamResolver`). No hay, hoy, ningún mecanismo para que un
   usuario opere sobre varias instituciones a la vez.

Hacía falta decidir tanto el modelo de datos para estos equipos como el
alcance de lo que ven en una sesión.

## Decisión

### Modelo de datos: un `User` con roles en varias instituciones, no una entidad aparte

Un técnico itinerante **es un `User` normal**. Lo único que lo distingue es:

- Tiene el mismo rol de spatie (`EquipoTecnicoProvincial`, con el mismo
  mecanismo de teams por `institucion_id` que `RolInstitucional`) asignado
  en **varias** instituciones a la vez — tantas como EPI recorra — en vez de
  en una sola. El sistema ya soporta esto sin cambios: un usuario puede
  tener un rol distinto (o el mismo) en cada institución a la que
  pertenece, ver `RolPorInstitucionTest`.
- Una columna nueva, `users.equipo_tecnico_provincial` (nullable, casteada
  al enum `EquipoTecnicoProvincial`), marca que esa persona depende de la
  Dirección Provincial y no de una EPI, y de cuál de los tres equipos se
  trata. Es metadato de identidad organizacional, no el mecanismo de
  autorización: la autorización real sigue siendo el rol de spatie asignado
  institución por institución, igual que para cualquier otro usuario.

No se creó ninguna tabla de personas paralela (`tecnicos_provinciales` o
similar): hubiera duplicado el padrón que ya vive en `users`, y habría que
mantener sincronizados dos lugares con los mismos datos personales
(nombre, email, credenciales) para las mismas personas.

`equipo_tecnico_provincial` queda fuera de los atributos mass-assignable de
`User`, igual que `is_superadmin`: otorga acceso transversal a
instituciones y no debe poder fijarse desde un formulario genérico.

### Alcance en sesión: una institución activa por vez, igual que cualquier usuario

Un técnico que recorre seis EPI **selecciona una institución activa por
vez**, igual que el resto de los usuarios. No se construye una vista
consolidada multi-institución en esta etapa.

Motivos:

- `InstitucionScope` e `InstitucionTeamResolver` están construidos sobre una
  única institución activa (ADR-002); es el mecanismo de aislamiento de
  datos ya probado y en producción (issue "Aislamiento de datos por
  institución"). Convertirlo en multi-institución significa reescribir el
  scope que filtra *toda* consulta de negocio de la aplicación, antes de
  que exista un solo módulo de negocio construido sobre él. Es un cambio
  estructural de alto riesgo para un caso de uso que hoy tiene un puñado de
  personas.
- La guía no describe a estos equipos comparando o agregando datos de
  varias EPI a la vez: describe reuniones presenciales en una institución
  puntual ("estas reuniones deben realizarse de forma presencial para poder
  acceder a los registros... y al legajo de la niña o niño", cap. 6.1 y
  6.2) y "recorridos" — es decir, visitas secuenciales, una institución por
  vez. El propio backlog escopa el dashboard a "una institución completa"
  (issue "Dashboard con métricas reales"), no a una vista provincial.
- Una vista provincial consolidada (por ejemplo, para la Dirección
  Provincial en sí) es una necesidad real pero distinta: implicaría un modo
  de consulta agregada que deliberadamente cruce el scope por institución,
  y merece su propia decisión y su propio issue cuando haya una historia de
  usuario concreta para ella. No hace falta resolverla para que un técnico
  pueda trabajar hoy.
- No existe todavía ningún selector de institución activa en la aplicación
  (M0 no llegó a construir UI). Es una pieza genérica que cualquier usuario
  con más de una institución va a necesitar; los técnicos itinerantes son,
  simplemente, los primeros en necesitarla antes.

## Opciones consideradas

### Modelo de datos

#### Opción A: `User` con rol de spatie repetido por institución (elegida)

| Dimensión | Evaluación |
|---|---|
| Complejidad | Baja: reutiliza el mecanismo de teams que ya existe |
| Costo | Una fila de rol por institución que cubre; sin tablas nuevas de personas |
| Consistencia | Un solo padrón de personas (`users`); nada que sincronizar |
| Automatización necesaria | Al crear una institución, matricular ahí a los técnicos ya existentes (`ProvisionadorDeRolesProvinciales::sincronizarTecnicos`) |

**A favor:** cero conceptos nuevos en el modelo de datos de personas; el
aislamiento por institución sigue siendo el único mecanismo de acceso.
**En contra:** ninguno relevante; la única automatización que hace falta
(matricular técnicos en instituciones nuevas) ya tiene un punto de enganche
natural en `InstitucionObserver`.

#### Opción B: tabla de personas paralela para técnicos provinciales

| Dimensión | Evaluación |
|---|---|
| Complejidad | Alta: un segundo padrón de personas con sus propias credenciales o una relación 1:1 con `users` |
| Costo | Datos personales duplicados o una tabla que solo aporta una fila de metadato |
| Consistencia | Riesgo de que el mismo técnico exista en dos lugares con datos desalineados |
| Escalabilidad | No aporta nada que un campo en `users` no resuelva |

**A favor:** ninguno identificado para el tamaño de este problema.
**En contra:** duplica el padrón de personas, que es exactamente lo que se
buscaba evitar.

### Alcance en sesión

#### Opción A: una institución activa por vez (elegida)

Ver justificación en la sección Decisión.

#### Opción B: vista consolidada multi-institución

| Dimensión | Evaluación |
|---|---|
| Complejidad | Alta: `InstitucionScope` pasa de filtrar por un id a filtrar por un conjunto, en cada modelo de negocio existente y futuro |
| Costo | Reescritura de la pieza central de aislamiento antes de tener módulos de negocio para validarla |
| Riesgo | Cualquier query que no se actualice al nuevo contrato multi-institución puede filtrar de más o de menos |
| Alcance real | La guía no pide esto; lo más cercano es un futuro panel provincial, una necesidad distinta |

**A favor:** evitaría que el técnico cambie de institución activa a mano.
**En contra:** reescribe la base de seguridad de todo el sistema para un
puñado de usuarios, sin un requisito concreto de la guía que lo pida.

## Consecuencias

- Se vuelve más fácil: dar de alta un nuevo equipo técnico provincial es
  fijar una columna en `users` y dejar que `InstitucionObserver` lo
  matricule automáticamente en cada institución nueva.
- Se vuelve más difícil: nada nuevo — reutiliza mecanismos ya construidos y
  probados (teams de spatie, `institucion_user`).
- Queda pendiente, fuera del alcance de este ADR:
  - **Selector de institución activa**: hoy no hay ninguna UI para elegirla;
    hace falta para cualquier usuario multi-institución, no solo para
    técnicos. Es un ítem de backlog nuevo, no cubierto por M0-M6 tal como
    están redactados.
  - **Alta de un técnico nuevo después de que ya existen instituciones**:
    `ProvisionadorDeRolesProvinciales::enrolarTecnico()` matricula a un
    técnico en una institución puntual, pero nada llama todavía a
    matricularlo en las instituciones *ya existentes* cuando se lo designa
    (no existe aún ninguna pantalla de alta de personal). Cuando se
    construya esa función (M4, "CRUD de personal", o una futura gestión de
    usuarios), ese flujo debe recorrer `Institucion::all()` y llamar a
    `enrolarTecnico()` una vez por cada una.
  - **Vista provincial consolidada**: si en algún momento la Dirección
    Provincial necesita comparar datos entre EPI, es una decisión y un
    diseño aparte (probablemente un modo de consulta explícitamente fuera
    de `InstitucionScope`, con su propia autorización), no una extensión de
    este ADR.
  - Revisar si el supuesto "recorren todos los espacios" (cap. 2, pág. 15)
    sigue siendo válido si la provincia llega a tener muchas más EPI de las
    seis que nombra la guía; en ese caso podría hacer falta cubrir zonas en
    vez de la totalidad, y `sincronizarTecnicos` tendría que dejar de
    matricular automáticamente en *todas* las instituciones.
