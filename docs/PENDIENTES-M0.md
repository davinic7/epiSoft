# Pendientes de M0 · Fundaciones

Estado al 2026-09-21. Fuente de verdad de los criterios: [BACKLOG.md](BACKLOG.md).
M0 tiene **41 criterios**; **36 están cumplidos** (los dos que esperaban a
M1 —niños— ya se cumplen: `Nino`, `Referente` y `VacunaAplicada` llevan
`institucion_id` vía `PerteneceAInstitucion`, y `Nino` llama a
`registrarAcceso()` cada vez que se abre un legajo) y **5 faltan**, todos
bloqueados por algo externo.

## Bloqueados por algo externo

| Criterio | Qué falta |
|---|---|
| Layout: sidebar, topbar, navegación y modales | El HTML del prototipo original no está en el repositorio (`docs/prototipo/` solo tiene el README). Hay que pasarlo. |
| Layout: CSS del prototipo reutilizado | Ídem. |
| Layout: responsive del prototipo conservado | Ídem. |
| Política de datos: plazo de conservación y quién accede a cada categoría | El plazo post-baja está "pendiente de definir con el organismo" (ver [política](politica-tratamiento-datos-personales.md), sección "Pendiente de definir"). |
| Política de datos: revisada contra la Ley 25.326 | Requiere revisión con el organismo o asesoría legal. |

## Notas para quien siga

- **Entorno local:** para ver la auditoría en pantalla hace falta `php artisan migrate`, una institución activa y un usuario con permiso `auditoria.ver` (el superadmin lo tiene siempre).
- **La consola no audita:** `audit.console = false`. Lo creado por `db:seed`, `tinker` o `artisan` no queda en la auditoría.
- **Selector de institución:** al cambiar de institución la página se recarga. Si estaba abierta una ruta con un registro de la anterior, da 404.
- **HTML sin escapar:** el único `{!! !!}` permitido es el QR de doble factor. Hay un test que falla si aparece otro.
- **SQL crudo:** hay un test que falla si se usa `whereRaw`, `DB::raw` u otros en `app/` o en los seeders.
- **Antes de abrir un PR:** correr `composer run types:check` además de los tests; el CI ejecuta PHPStan y ya falló una vez por eso.
