# Pendientes de M0 · Fundaciones

Estado al 2026-09-19. Fuente de verdad de los criterios: [BACKLOG.md](BACKLOG.md).
M0 tiene **41 criterios**; **31 están cumplidos** (con el PR de tests de
seguridad mergeado) y **10 faltan**.

## Bloqueados por algo externo

| Criterio | Qué falta |
|---|---|
| Layout: sidebar, topbar, navegación y modales | El HTML del prototipo original no está en el repositorio (`docs/prototipo/` solo tiene el README). Hay que pasarlo. |
| Layout: CSS del prototipo reutilizado | Ídem. |
| Layout: responsive del prototipo conservado | Ídem. |
| Política de datos: plazo de conservación y quién accede a cada categoría | El plazo post-baja está "pendiente de definir con el organismo" (ver [política](politica-tratamiento-datos-personales.md), sección "Pendiente de definir"). |
| Política de datos: revisada contra la Ley 25.326 | Requiere revisión con el organismo o asesoría legal. |

## Se pueden hacer ya

| Criterio | Qué hay que hacer |
|---|---|
| Un solo comando levanta la app y la base de datos | Hoy el README exige un MySQL/MariaDB local y varios comandos (`composer setup`, `composer dev`). Falta una forma de un solo paso, por ejemplo con contenedores. |
| Documentado en README para que otra persona lo levante sin ayuda | Se cierra junto con el anterior. Incluir: variables de `.env`, base de datos, seed y usuario de prueba. |
| Índices en claves foráneas y campos de búsqueda frecuente | Revisar las migraciones existentes; los campos de búsqueda se conocerán mejor con M1. |

## Esperan a M1 (niños y asistencia)

| Criterio | Por qué espera |
|---|---|
| Todas las tablas de negocio llevan `institucion_id` | Todavía no hay tablas de negocio. El trait `PerteneceAInstitucion` ya está listo para usarse. |
| Se registra todo acceso y modificación a datos sensibles de niños | Todavía no existe el modelo de niños. El modelo debe usar el trait `AuditaCambios` y llamar a `registrarAcceso()` cada vez que un usuario abre un legajo. |

## Dónde continuar (2026-09-19)

Hay tres PRs abiertos, todos desde `main`, sin mergear:

| PR | Rama | Qué trae |
|---|---|---|
| [#79](https://github.com/davinic7/epiSoft/pull/79) | `feat/indices-listado` | Índices de los listados y entorno Docker con Sail (`composer docker:setup`). **El entorno Docker no está probado**: falta correrlo de punta a punta. Hasta entonces no tildar "Un solo comando levanta la app" ni "Documentado en README". |
| [#80](https://github.com/davinic7/epiSoft/pull/80) | `feat/m1-salas` | M1: CRUD de salas con turno y capacidad. Turnos (mañana, tarde, jornada completa) y capacidad máxima de 200 son supuestos a confirmar. |
| [#81](https://github.com/davinic7/epiSoft/pull/81) | `feat/m1-ninos` | M1: CRUD de niños (listado, edición, legajo con auditoría de acceso). Falta el formulario en tres pasos. |

Orden sugerido:

1. Revisar y mergear #79, #80 y #81. **#80 y #81 chocan** en `routes/web.php`,
   `resources/views/layouts/app/sidebar.blade.php` y `app/Models/Audit.php`:
   quien entre segundo resuelve un conflicto trivial (conservar ambas líneas).
2. Probar a mano las pantallas `/salas` y `/ninos` (solo se probaron con tests)
   y `composer docker:setup` en una máquina con Docker (en Windows, con WSL 2).
3. Con #80 y #81 en `main`: **asignar niños a salas y avisar al superar el
   cupo** (cierra el issue "Salas, turnos y cupos"). Requiere agregar
   `sala_id` a `ninos`.
4. Después, en M1: referentes y vínculo familiar, alergias y restricciones
   alimentarias, vacunas, asistencia diaria.
5. Cuando llegue el HTML del prototipo: layout de M0 y formulario en tres pasos
   del alta de niños.

Con los niños ya modelados, revisar dos criterios de M0 que quedaron abiertos:
"Todas las tablas de negocio llevan `institucion_id`" (`salas` y `ninos` ya lo
llevan) y "Se registra todo acceso a datos sensibles de niños" (el legajo lo
hace; falta para los datos que se sumen).

## Notas para quien siga

- **Correr comandos en Windows:** `php` está en PowerShell (Herd), no en Git Bash.

- **Entorno local:** para ver la auditoría en pantalla hace falta `php artisan migrate`, una institución activa y un usuario con permiso `auditoria.ver` (el superadmin lo tiene siempre).
- **La consola no audita:** `audit.console = false`. Lo creado por `db:seed`, `tinker` o `artisan` no queda en la auditoría.
- **Selector de institución:** al cambiar de institución la página se recarga. Si estaba abierta una ruta con un registro de la anterior, da 404.
- **HTML sin escapar:** el único `{!! !!}` permitido es el QR de doble factor. Hay un test que falla si aparece otro.
- **SQL crudo:** hay un test que falla si se usa `whereRaw`, `DB::raw` u otros en `app/` o en los seeders.
- **Antes de abrir un PR:** correr `composer run types:check` además de los tests; el CI ejecuta PHPStan y ya falló una vez por eso.
