# Backlog — Sistema EPI

Sistema de gestión integral para Espacios de Primera Infancia. Despliegue web único, multi-institución.

**62 issues** repartidos en 7 milestones.

El archivo `backlog.txt` es la fuente de verdad: editalo y volvé a correr `setup-github-project.sh`.


## Resumen

| Milestone | Issues | Foco |
|---|---|---|
| M0 · Fundaciones | 13 | Decisiones, esqueleto, seguridad y multi-institución. Nada de negocio hasta que esto cierre. |
| M1 · Niños y asistencia | 10 | El núcleo del sistema: legajos, salas, referentes, salud y asistencia diaria. |
| M2 · Economato | 9 | Stock por lote, libro de movimientos, menú semanal e inventario patrimonial. |
| M3 · Pedagógico | 8 | Registro diario, seguimientos, desafíos del desarrollo e informes. |
| M4 · RRHH | 6 | Personal, documentación con vencimientos, capacitaciones y horarios. |
| M5 · Institucional y alertas | 8 | Mapa de riesgos, vulneración de derechos, articulaciones y motor de alertas. |
| M6 · Endurecimiento y salida a producción | 8 | Backups, despliegue, pruebas, rendimiento y capacitación. |

## Issues

### M0 · Fundaciones

_Decisiones, esqueleto, seguridad y multi-institución. Nada de negocio hasta que esto cierre._

#### ADR-001: elegir stack backend definitivo
`area:core` · `prioridad:alta` · `estimación:S`

**Criterios de aceptación**

- [x] Documentar 2-3 opciones con pros y contras
- [x] Registrar la decisión y sus consecuencias en docs/adr/001-stack.md
- [x] Marcar el ADR como Aceptado antes de escribir código de aplicación

#### ADR-002: estrategia de multi-institución
`area:core` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Comparar base compartida con institucion_id contra base por institución
- [x] Definir cómo se resuelve la institución activa en cada request
- [x] Documentar el impacto en backups y en migraciones

#### Estructura del repositorio y convenciones
`area:infra` · `prioridad:alta` · `estimación:S`

**Criterios de aceptación**

- [x] README con descripción del sistema y cómo levantarlo
- [x] .gitignore, .editorconfig y convención de commits definidas
- [x] Rama main protegida

#### Entorno de desarrollo reproducible
`area:infra` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Un solo comando levanta la app y la base de datos
- [x] Datos de prueba (seed) de una institución ficticia
- [x] Documentado en README para que otra persona lo levante sin ayuda

#### Esquema inicial de base de datos y sistema de migraciones
`area:core` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Migraciones versionadas y aplicables en orden
- [x] Todas las tablas de negocio llevan institucion_id
- [x] Índices en las claves foráneas y en los campos de búsqueda frecuente

#### Tabla instituciones y alta de institución
`area:core` · `prioridad:alta` · `estimación:S`

**Criterios de aceptación**

- [x] CRUD de instituciones accesible solo para superadmin
- [x] Datos: nombre, dirección, CUIT, referente, capacidad
- [x] Al menos una institución cargada por seed

#### Autenticación de usuarios
`area:seguridad` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Login y logout con sesión segura
- [x] Contraseñas con hash bcrypt o Argon2
- [x] Recuperación de contraseña por correo
- [x] Bloqueo temporal tras N intentos fallidos

#### Modelo de roles y permisos
`area:seguridad` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Roles definidos según cap. 2 de la guía de buenas prácticas: superadmin, equipo de coordinación, encargado de recepción, coordinador pedagógico, educador, encargado de economato, personal de cocina, personal de mantenimiento y limpieza (los equipos itinerantes de la Dirección Provincial quedan fuera, pendientes de modelado propio)
- [x] Permisos por módulo y por acción (ver, crear, editar, eliminar)
- [x] Un usuario pertenece a una o más instituciones con rol por institución

#### Aislamiento de datos por institución
`area:seguridad` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Toda consulta filtra por la institución activa de forma automática
- [x] Un usuario no puede acceder a un registro de otra institución ni cambiando el ID en la URL
- [x] Prueba automatizada que verifica el aislamiento

#### Registro de auditoría
`area:seguridad` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Tabla de auditoría con usuario, acción, entidad, fecha e IP
- [ ] Se registra todo acceso y modificación a datos sensibles de niños
- [x] Pantalla de consulta de auditoría para equipo de coordinación y superadmin

#### Protecciones básicas de la aplicación web
`area:seguridad` · `prioridad:alta` · `estimación:S`

**Criterios de aceptación**

- [ ] Tokens CSRF en todos los formularios
- [ ] Consultas parametrizadas en todo el acceso a datos
- [ ] Escapado de salida para prevenir XSS
- [x] Cabeceras de seguridad configuradas

#### Portar el layout base del prototipo
`area:ux` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [ ] Sidebar, topbar, navegación entre módulos y sistema de modales funcionando
- [ ] CSS del prototipo reutilizado
- [ ] Comportamiento responsive del prototipo conservado

#### Política de tratamiento de datos personales
`area:core` · `prioridad:media` · `estimación:S`

**Criterios de aceptación**

- [x] Documento que identifica qué datos sensibles se almacenan y con qué finalidad
- [ ] Definido el plazo de conservación y quién accede a cada categoría
- [ ] Revisado contra la Ley 25.326

### M1 · Niños y asistencia

_El núcleo del sistema: legajos, salas, referentes, salud y asistencia diaria._

#### CRUD de niños con alta en tres pasos
`area:ninos` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Formulario en tres pasos como en el prototipo¹
- [x] Validación de DNI único por institución
- [x] Edición y consulta del legajo
- [x] Listado con paginación

¹ El prototipo HTML no está en el repositorio (ver "Layout" en
[PENDIENTES-M0.md](PENDIENTES-M0.md)), así que los tres pasos
(identificación, domicilio, datos institucionales) son un diseño propio
basado en los criterios de esta historia, no una copia del prototipo.
Referentes, salud y vacunas quedan fuera de este alta: se cargan después
sobre el legajo ya creado, en sus propias historias.

#### Salas, turnos y cupos
`area:ninos` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] CRUD de salas con turno y capacidad máxima
- [ ] Asignación de un niño a una sala
- [ ] El sistema avisa al superar el cupo de la sala

#### Referentes y vínculo familiar
`area:ninos` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Alta de referentes con DNI, contacto y parentesco
- [x] Un niño puede tener varios referentes y un referente varios niños
- [x] Marcado de quién está autorizado a retirar al niño

#### Carnet de vacunación
`area:ninos` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Registro de vacunas aplicadas con fecha
- [x] Estado calculado según calendario nacional por edad²
- [x] Listado de niños con vacunas atrasadas

² El calendario (`config/calendario_vacunacion.php`) es un borrador
armado con conocimiento general del esquema argentino, **sin
contrastar contra la fuente oficial** del Ministerio de Salud ni
revisado por personal de salud. Hay que validarlo antes de confiar en
el estado "atrasada" para decisiones reales; corregirlo no requiere
tocar código, solo ese archivo.

#### Alergias y restricciones alimentarias
`area:ninos` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Registro por niño con tipo, severidad y observación
- [x] Visible de forma destacada en el legajo y en la vista de sala (nueva, `salas.ver`)
- [ ] Consultable desde el módulo de economato — espera a que exista el módulo (M2); `Nino::alergias()` ya queda listo para que economato lo consulte sin cambios de esquema.

#### Asistencia diaria por sala
`area:ninos` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Toma de asistencia del día por sala y turno (el turno ya es propio de la sala, ver `Turno`)
- [x] Registro de quién retira al niño (limitado a sus referentes autorizados a retirar)
- [x] Reporte mensual de asistencia por niño y por sala

#### Vista de legajo con pestañas
`area:ninos` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [x] Pestañas: datos personales, referentes, salud, asistencia, seguimiento³
- [x] Carga de los datos reales desde la base
- [x] Impresión del legajo completo (con `window.print()` y CSS `print:`)

³ La pestaña "seguimiento" muestra un aviso de "todavía no disponible":
el seguimiento pedagógico individual es una historia de M3 que todavía
no existe.

#### Búsqueda y filtros del listado de niños
`area:ninos` · `prioridad:media` · `estimación:S`

**Criterios de aceptación**

- [x] Búsqueda por nombre, alias y DNI
- [x] Filtros por sala, turno y estado⁴
- [x] Los filtros se conservan al navegar (`#[Url]`, como en auditoría)

⁴ "Estado" usa el soft delete que ya tenía `Nino` (activos por
defecto, de baja, o todos). La acción de baja en sí se agregó después,
en la historia "Egreso y baja de niños".

#### Egreso y baja de niños
`area:ninos` · `prioridad:media` · `estimación:S`

**Criterios de aceptación**

- [x] Baja lógica con motivo y fecha
- [x] Los niños dados de baja no aparecen en listados activos
- [x] Historial consultable (filtro "De baja"/"Todos" del listado, con motivo y fecha; restaurable)

#### Importación inicial de datos existentes
`area:ninos` · `prioridad:baja` · `estimación:M`

**Criterios de aceptación**

- [x] Carga desde planilla con los legajos actuales⁵
- [x] Reporte de filas rechazadas con el motivo
- [x] Proceso repetible sin duplicar registros

⁵ Solo CSV, no `.xlsx`: el proyecto no tiene una librería de lectura de
Excel entre sus dependencias y agregarla requiere aprobación (ver
CLAUDE.md, "no cambiar dependencias sin aprobación"). Cualquier planilla
real se puede exportar a CSV desde Excel, Google Sheets o LibreOffice.
Cada fila se valida por separado (una fila con errores no impide
importar el resto) y una fila cuyo DNI ya existe en la institución
(activo o dado de baja) se rechaza en vez de duplicar el registro.

### M2 · Economato

_Stock por lote, libro de movimientos, menú semanal e inventario patrimonial._

#### Catálogo de artículos y categorías
`area:economato` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [x] Categorías: alimentos, limpieza, librería y pedagogía
- [x] Alta de artículo con unidad de medida y stock mínimo
- [x] Búsqueda y filtro por categoría

#### Stock por lote con vencimiento
`area:economato` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Cada ingreso genera un lote con fecha de vencimiento
- [x] El stock actual se calcula desde los movimientos, no se edita a mano⁶
- [x] Vista de stock por artículo con desglose de lotes

⁶ El lote no tiene un campo de cantidad editable: cada ingreso crea,
además del lote, un movimiento de tipo "entrada" (`MovimientoStock`), y
`Lote::stockActual()` suma sus movimientos en vez de leer un campo. El
libro de movimientos completo —salidas, consumo del lote que vence
antes y anulación por contramovimiento— es la próxima historia, "Libro
de movimientos de entrada y salida"; esta historia ya deja el modelo de
datos listo para que esa agregue el tipo "salida" sin migrar de nuevo.

#### Libro de movimientos de entrada y salida
`area:economato` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [x] Registro de movimiento con fecha, artículo, cantidad, origen y quién recibe o entrega⁷
- [x] No se permite una salida mayor al stock disponible
- [x] Las salidas consumen primero el lote que vence antes
- [x] Los movimientos no se editan, se anulan con contramovimiento⁸

⁷ Los ingresos se registran desde la vista de Stock (crean un lote
nuevo); esta pantalla es el libro completo y donde se registran las
salidas. "Origen" es de dónde viene una entrada (Dirección Provincial,
donación) o el destino de una salida (guía, cap. 3.4, pág. 23: "ya sea
fuera de la institución o para uso interno"); "quién recibe o entrega"
es un único campo de contraparte, porque cuál de los dos es depende del
tipo de movimiento.

⁸ Anular crea un movimiento de signo contrario (`anula_a_id` referencia
al original) en vez de editar o borrar. Anular una entrada se rechaza
si su lote ya fue consumido por una salida posterior, para no dejar el
lote en stock negativo.

#### Alertas de vencimiento y stock mínimo
`area:economato` · `prioridad:alta` · `estimación:S`

**Criterios de aceptación**

- [x] Aviso configurable de días previos al vencimiento⁹
- [x] Aviso cuando el stock cae bajo el mínimo del artículo
- [x] Las alertas aparecen en el dashboard

⁹ `dias_aviso_vencimiento` es una columna de `instituciones` (default
30), editable desde Economato → Alertas con permiso `economato.editar`;
no pasa por el CRUD de instituciones porque ese es exclusivo del
superadmin (ver `App\Enums\Modulo`, comentario sobre "instituciones").
El dashboard muestra un resumen (`AlertasDashboard`) para quien tenga
`economato.ver`; la pantalla completa vive en Economato → Alertas.

#### Menú semanal
`area:economato` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [x] Carga del menú por día y por comida¹⁰
- [x] Circuito de aprobación por el rol de nutrición¹¹
- [x] Historial de menús de semanas anteriores

¹⁰ Días lunes a viernes y momentos de alimentación (desayuno, colación,
almuerzo, merienda reforzada) según la guía, Anexo 1, Ficha 5, pág. 61.
Un menú nuevo arma las 20 casillas vacías de la semana; cada una se
edita por separado y no tiene un paso intermedio de envío a revisión.

¹¹ No hay un rol institucional de nutrición (ver ADR-003): aprobar
exige el rol `EquipoTecnicoProvincial::Nutricion`, chequeado con
`hasRole()` en vez del permiso `economato.editar` (que también tiene
el encargado de economato, y no debería poder autoaprobar su propio
borrador). `ProvisionadorDeRolesProvinciales` ya documentaba esta
decisión desde antes de esta historia. Un menú aprobado no se edita.

#### Cruce del menú con alergias activas
`area:economato` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [x] Al aprobar un menú el sistema advierte qué niños tienen restricción con esos ingredientes¹²
- [x] Listado imprimible de restricciones por sala para la cocina¹³

¹² Ni el menú ni la alergia tienen una lista estructurada de
ingredientes (ambos son texto libre), así que el cruce es una
coincidencia de texto simple: si la descripción de la alergia aparece
dentro de la descripción de una casilla, avisa. El aviso se muestra en
todo momento en el editor del menú (no solo al aprobar) y no bloquea
la aprobación: el equipo de nutrición decide con esa información.

¹³ Vive en Economato, no en Salas → Ver (que ya mostraba lo mismo
desde la historia "Alergias y restricciones alimentarias" de M1),
porque esa pantalla exige `ninos.ver` y el personal de cocina no tiene
ese permiso (solo `economato.ver`). Esta pantalla nueva expone lo
mínimo necesario —sala, niño/a, alergia— sin dar acceso al legajo.

#### Inventario patrimonial
`area:economato` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [x] Alta de bien con código, ubicación y estado de conservación
- [x] Registro de movimientos de ubicación¹⁴
- [x] Reporte de inventario por ubicación

¹⁴ La ubicación de un bien no se edita desde el formulario general:
cambiarla es la acción "Mover" (`Bien::moverA()`), que además de
actualizar la ubicación actual deja el movimiento anterior→nueva en
`movimientos_de_ubicacion`.

#### Reportes de consumo
`area:economato` · `prioridad:baja` · `estimación:M`

**Criterios de aceptación**

- [ ] Consumo por artículo y por período
- [ ] Comparación entre períodos
- [ ] Exportación a planilla

#### Cierre mensual de economato
`area:economato` · `prioridad:baja` · `estimación:S`

**Criterios de aceptación**

- [ ] Bloqueo de movimientos de períodos cerrados
- [ ] Reporte de cierre con saldos iniciales y finales
- [ ] Solo el equipo de coordinación puede reabrir un período

### M3 · Pedagógico

_Registro diario, seguimientos, desafíos del desarrollo e informes._

#### Libro de registro diario por sala
`area:pedagogico` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Registro diario por sala con novedades, actividades y observaciones
- [ ] Un registro por sala y por día, editable solo en el día en curso
- [ ] Consulta del historial por rango de fechas

#### Seguimientos individuales
`area:pedagogico` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Apertura de seguimiento con motivo y responsable
- [ ] Bitácora de entradas fechadas dentro del seguimiento
- [ ] Estados: activo, en pausa, cerrado
- [ ] Listado de seguimientos activos en el dashboard

#### Desafíos en el desarrollo infantil temprano
`area:pedagogico` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [ ] Registro por niño con área del desarrollo y descripción
- [ ] Vinculación con un seguimiento activo
- [ ] Historial por niño

#### Informe de evolución individual
`area:pedagogico` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Generación del informe desde los datos del seguimiento y del registro diario
- [ ] Edición del texto antes de cerrar el informe
- [ ] Exportación a PDF y a Word con membrete de la institución

#### Informe semestral institucional
`area:pedagogico` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Armado del informe con datos consolidados del semestre
- [ ] Control de qué informes están pendientes por sala
- [ ] Exportación al formato que exige el organismo

#### Agenda de actividades y planificaciones
`area:pedagogico` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Carga de actividades con fecha, sala y responsable
- [ ] Vista de próximas actividades en el dashboard
- [ ] Marcado de actividad realizada

#### Adjuntos en legajos e informes
`area:pedagogico` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Carga de archivos con límite de tamaño y tipos permitidos
- [ ] Los archivos no son accesibles por URL directa sin permiso
- [ ] Registro en auditoría de cada descarga

#### Plantillas de informe configurables
`area:pedagogico` · `prioridad:baja` · `estimación:S`

**Criterios de aceptación**

- [ ] El equipo de coordinación puede editar los textos base de cada tipo de informe
- [ ] Las plantillas son propias de cada institución

### M4 · RRHH

_Personal, documentación con vencimientos, capacitaciones y horarios._

#### CRUD de personal
`area:rrhh` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [ ] Alta con datos personales, función y fecha de ingreso
- [ ] Listado con filtro por función y estado
- [ ] Baja lógica con motivo

#### Documentación obligatoria con vencimientos
`area:rrhh` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Tipos: antecedentes penales, acta de confidencialidad, libreta sanitaria, título
- [ ] Cada documento con fecha de emisión, vencimiento y archivo adjunto
- [ ] Semáforo de estado: vigente, por vencer, vencido
- [ ] Alerta automática antes del vencimiento

#### Capacitaciones y constancias
`area:rrhh` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Registro de capacitación con fecha, institución y carga horaria
- [ ] Adjunto de la constancia
- [ ] Reporte de capacitaciones por persona y por período

#### Horarios y funciones
`area:rrhh` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Carga del horario semanal por persona y turno
- [ ] Vista de cobertura por sala y turno
- [ ] Detección de salas sin personal asignado en un turno

#### Vista de legajo de personal
`area:rrhh` · `prioridad:media` · `estimación:S`

**Criterios de aceptación**

- [ ] Pestañas: datos, documentación, capacitaciones, horario
- [ ] Impresión del legajo
- [ ] Acceso restringido al equipo de coordinación

#### Registro de ausencias y licencias
`area:rrhh` · `prioridad:baja` · `estimación:M`

**Criterios de aceptación**

- [ ] Carga de licencia con tipo, período y certificado
- [ ] Impacto visible en la vista de cobertura
- [ ] Reporte de ausentismo

### M5 · Institucional y alertas

_Mapa de riesgos, vulneración de derechos, articulaciones y motor de alertas._

#### Mapa de riesgos
`area:institucional` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [ ] Checklist por área con estado y observación
- [ ] Registro de verificaciones periódicas con fecha y responsable
- [ ] Alerta cuando una verificación está vencida

#### Módulo de vulneración de derechos
`area:institucional` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Módulo propio, separado de Institucional, con acceso restringido solo al equipo de coordinación y sin permiso de eliminar registros
- [ ] Bitácora de intervenciones y derivaciones
- [ ] Auditoría reforzada: se registra toda consulta al registro
- [ ] Los datos no aparecen en listados ni reportes generales

#### Articulaciones interinstitucionales
`area:institucional` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Registro de articulación con institución, tipo, referente y fecha
- [ ] Seguimiento del estado de la articulación
- [ ] Próximas articulaciones en el dashboard

#### Recursero comunitario
`area:institucional` · `prioridad:media` · `estimación:S`

**Criterios de aceptación**

- [ ] Directorio de instituciones con tipo, referente y contacto
- [ ] Búsqueda y filtro por tipo
- [ ] Compartido entre instituciones o propio, según se defina

#### Motor de alertas derivadas
`area:alertas` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Las alertas se calculan desde los datos, no se cargan a mano
- [ ] Fuentes: vencimientos de stock, stock mínimo, documentación de personal, vacunas, informes pendientes, verificaciones de riesgo
- [ ] Cada alerta tiene severidad y enlace al registro de origen
- [ ] Posibilidad de posponer o descartar con motivo

#### Dashboard con métricas reales
`area:alertas` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Métricas calculadas desde la base: matrícula, asistencia del día, alertas activas
- [ ] Ingresos del día y próximas actividades
- [ ] Carga por debajo de dos segundos con datos de una institución completa

#### Exportación de documentos a PDF y Word
`area:core` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Membrete y pie institucional en todos los documentos
- [ ] Generación consistente entre informes, legajos y reportes
- [ ] Nombre de archivo predecible

#### Notificaciones por correo
`area:alertas` · `prioridad:baja` · `estimación:M`

**Criterios de aceptación**

- [ ] Resumen diario o semanal de alertas críticas al equipo de coordinación
- [ ] Configuración de frecuencia por usuario
- [ ] Baja de la suscripción desde el correo

### M6 · Endurecimiento y salida a producción

_Backups, despliegue, pruebas, rendimiento y capacitación._

#### Backups automáticos y restauración probada
`area:infra` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [ ] Backup diario automático de base de datos y archivos adjuntos
- [ ] Copia fuera del servidor de producción
- [ ] Restauración probada al menos una vez y documentada

#### Despliegue en producción
`area:infra` · `prioridad:alta` · `estimación:M`

**Criterios de aceptación**

- [ ] Dominio propio con HTTPS
- [ ] Variables de entorno fuera del repositorio
- [ ] Procedimiento de despliegue documentado y repetible

#### Monitoreo y registro de errores
`area:infra` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Errores de producción registrados con contexto
- [ ] Aviso ante caída del servicio
- [ ] Los errores no exponen datos sensibles al usuario final

#### Pruebas de los módulos críticos
`area:core` · `prioridad:alta` · `estimación:L`

**Criterios de aceptación**

- [ ] Cobertura de: aislamiento por institución, permisos por rol, cálculo de stock y motor de alertas
- [ ] Las pruebas corren en CI ante cada push
- [ ] La rama main no acepta merge con pruebas en rojo

#### Revisión de rendimiento e índices
`area:core` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Identificar y corregir consultas N+1
- [ ] Índices verificados sobre los listados principales
- [ ] Prueba con volumen de datos de tres años

#### Revisión responsive y uso en móvil
`area:ux` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Módulos usables en pantalla de teléfono
- [ ] Toma de asistencia cómoda desde el móvil
- [ ] Probado en Android e iOS

#### Manual de usuario y capacitación
`area:core` · `prioridad:media` · `estimación:M`

**Criterios de aceptación**

- [ ] Manual por rol con capturas
- [ ] Guía rápida de una página para educadoras/es
- [ ] Sesión de capacitación al equipo

#### Alta y onboarding de nuevas instituciones
`area:core` · `prioridad:baja` · `estimación:M`

**Criterios de aceptación**

- [ ] Procedimiento documentado para incorporar una EPI
- [ ] Datos iniciales cargados por asistente guiado
- [ ] Tiempo de alta menor a un día
