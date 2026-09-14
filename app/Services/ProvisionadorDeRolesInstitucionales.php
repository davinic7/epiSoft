<?php

namespace App\Services;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Enums\RolInstitucional;
use App\Models\Institucion;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Da de alta, para una institución, los roles institucionales estándar
 * (RolInstitucional) con su matriz de permisos por módulo y acción. Se
 * ejecuta automáticamente al crear una Institución (ver
 * App\Observers\InstitucionObserver) para que ninguna alta se olvide de
 * sembrar roles.
 *
 * Los roles y sus asignaciones a usuarios están escopados por institución
 * (spatie teams, ver config/permission.php); los permisos en sí no lo
 * están, porque el mismo catálogo de módulo.acción es compartido entre
 * instituciones y solo cambia qué rol tiene cada permiso.
 */
class ProvisionadorDeRolesInstitucionales
{
    private const string GUARD = 'web';

    /**
     * Matriz por defecto: qué acciones tiene cada rol en cada módulo. Un
     * módulo ausente para un rol significa que ese rol no tiene ningún
     * permiso ahí. Primer corte según docs/BACKLOG.md, pensado para
     * ajustarse a medida que cada módulo se construye.
     *
     * @var array<string, array<string, list<AccionPermiso>>>
     */
    private const array MATRIZ = [
        RolInstitucional::Direccion->value => [
            Modulo::Usuarios->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Ninos->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Economato->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Pedagogico->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Rrhh->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Institucional->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Alertas->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
        ],
        RolInstitucional::Administrativo->value => [
            Modulo::Usuarios->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            Modulo::Ninos->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            Modulo::Economato->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            Modulo::Rrhh->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            Modulo::Institucional->value => [AccionPermiso::Ver],
            Modulo::Alertas->value => [AccionPermiso::Ver, AccionPermiso::Editar],
        ],
        RolInstitucional::Docente->value => [
            Modulo::Ninos->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            Modulo::Pedagogico->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            Modulo::Economato->value => [AccionPermiso::Ver],
            Modulo::Alertas->value => [AccionPermiso::Ver],
        ],
        RolInstitucional::Nutricion->value => [
            Modulo::Economato->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            Modulo::Ninos->value => [AccionPermiso::Ver],
            Modulo::Alertas->value => [AccionPermiso::Ver],
        ],
    ];

    public function provisionar(Institucion $institucion): void
    {
        foreach (RolInstitucional::cases() as $rolInstitucional) {
            $rol = Role::query()->firstOrCreate([
                'name' => $rolInstitucional->value,
                'institucion_id' => $institucion->id,
                'guard_name' => self::GUARD,
            ]);

            foreach (self::MATRIZ[$rolInstitucional->value] as $moduloValue => $acciones) {
                $modulo = Modulo::from($moduloValue);

                $rol->givePermissionTo(array_map(
                    fn (AccionPermiso $accion): Permission => $this->permiso($modulo, $accion),
                    $acciones,
                ));
            }
        }
    }

    /**
     * Crea, si todavía no existe, todo el catálogo de permisos módulo.acción
     * que aparece en la matriz. Los roles se crean por institución, pero el
     * catálogo de permisos es único para toda la aplicación.
     */
    public function sembrarCatalogoDePermisos(): void
    {
        foreach (self::MATRIZ as $modulosPorRol) {
            foreach ($modulosPorRol as $moduloValue => $acciones) {
                $modulo = Modulo::from($moduloValue);

                foreach ($acciones as $accion) {
                    $this->permiso($modulo, $accion);
                }
            }
        }
    }

    private function permiso(Modulo $modulo, AccionPermiso $accion): Permission
    {
        return Permission::query()->firstOrCreate([
            'name' => $modulo->permiso($accion),
            'guard_name' => self::GUARD,
        ]);
    }
}
