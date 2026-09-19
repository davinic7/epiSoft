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

## Notas para quien siga

- **Entorno local:** para ver la auditoría en pantalla hace falta `php artisan migrate`, una institución activa y un usuario con permiso `auditoria.ver` (el superadmin lo tiene siempre).
- **La consola no audita:** `audit.console = false`. Lo creado por `db:seed`, `tinker` o `artisan` no queda en la auditoría.
- **Selector de institución:** al cambiar de institución la página se recarga. Si estaba abierta una ruta con un registro de la anterior, da 404.
- **HTML sin escapar:** el único `{!! !!}` permitido es el QR de doble factor. Hay un test que falla si aparece otro.
- **SQL crudo:** hay un test que falla si se usa `whereRaw`, `DB::raw` u otros en `app/` o en los seeders.
- **Antes de abrir un PR:** correr `composer run types:check` además de los tests; el CI ejecuta PHPStan y ya falló una vez por eso.
