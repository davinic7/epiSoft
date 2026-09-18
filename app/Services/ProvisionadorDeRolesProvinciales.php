<?php

namespace App\Services;

use App\Enums\AccionPermiso;
use App\Enums\EquipoTecnicoProvincial;
use App\Enums\Modulo;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Da de alta, para una institución, los roles de los equipos técnicos
 * itinerantes de la Dirección Provincial (EquipoTecnicoProvincial) con su
 * matriz de permisos, y matricula en esa institución a los técnicos que ya
 * existen. Se ejecuta automáticamente al crear una Institución (ver
 * App\Observers\InstitucionObserver), igual que
 * ProvisionadorDeRolesInstitucionales, del que es hermano.
 *
 * Ver docs/adr/003-equipos-tecnicos-provinciales.md para la decisión de
 * modelado: un técnico es un User normal con este mismo rol de spatie
 * asignado en varias instituciones (tantas filas como EPI recorra), no una
 * entidad ni una tabla de personas separada.
 */
class ProvisionadorDeRolesProvinciales
{
    private const string GUARD = 'web';

    /**
     * Matriz por defecto: qué acciones tiene cada equipo itinerante en cada
     * módulo, dentro de cada institución. Mismo formato y misma exigencia de
     * cita textual que ProvisionadorDeRolesInstitucionales::MATRIZ.
     *
     * Ningún equipo tiene Eliminar en Modulo::Vulneraciones: la regla de "sin
     * Eliminar" para ese módulo (ver docs/backlog.txt, issue "Módulo de
     * vulneración de derechos") aplica a todos los roles del sistema, no solo
     * a los institucionales.
     *
     * @var array<string, array<string, list<AccionPermiso>>>
     */
    private const array MATRIZ = [
        EquipoTecnicoProvincial::AbordajeGlobal->value => [
            // Valoración preliminar junto al equipo de coordinación ante
            // sospecha de violencia, y acompañamiento de los encuentros con
            // la familia (guía, cap. 6.1, pág. 40-45, líneas 752 y 829).
            Modulo::Vulneraciones->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // "Convocar al equipo técnico de abordaje global... para
            // fortalecer el plan de acción" ante desafíos en el desarrollo
            // (guía, cap. 6.2, pág. 46-51, líneas 821 y 851): acompaña, no
            // es autor de las planificaciones.
            Modulo::Pedagogico->value => [AccionPermiso::Ver, AccionPermiso::Editar],
            // Diagnósticos, relevamientos y mentorías institucionales (guía,
            // cap. 2, pág. 16, línea 439): deja constancia, no gestiona el
            // mapa de riesgo ni las articulaciones (eso es del equipo de
            // coordinación).
            Modulo::Institucional->value => [AccionPermiso::Ver, AccionPermiso::Editar],
        ],
        EquipoTecnicoProvincial::Nutricion->value => [
            // Cálculo de compra, evaluación de oferentes y control de stock
            // y calidad de alimentos (guía, cap. 2, pág. 16, línea 449); es
            // además el circuito de aprobación del menú semanal que pedía
            // docs/backlog.txt, issue "Menú semanal", sin un rol
            // institucional de nutrición que ya no existe (ver ADR-003).
            Modulo::Economato->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // Capacitaciones y talleres al personal de los espacios (guía,
            // cap. 2, pág. 16, línea 449): interviene en la formación, no
            // gestiona el legajo del personal.
            Modulo::Rrhh->value => [AccionPermiso::Ver],
        ],
        EquipoTecnicoProvincial::TrabajoSocial->value => [
            // Recibe la comunicación inicial junto al equipo de coordinación
            // y realiza la visita domiciliaria y el informe socioambiental
            // (guía, cap. 6.1, pág. 40-45, líneas 742 y 774).
            Modulo::Vulneraciones->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // Entrevistas integrales e informes socioambientales requieren
            // leer el legajo (guía, cap. 2, pág. 17, línea 455).
            Modulo::Ninos->value => [AccionPermiso::Ver],
            // "Articulaciones comunitarias e interinstitucionales (mapeo
            // territorial y trabajo en red)" es texto literal de la guía
            // para este equipo (cap. 2, pág. 17, línea 455) y es el mismo
            // texto de docs/backlog.txt, issue "Articulaciones
            // interinstitucionales".
            Modulo::Institucional->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
        ],
    ];

    public function provisionar(Institucion $institucion): void
    {
        foreach (EquipoTecnicoProvincial::cases() as $equipo) {
            $rol = Role::query()->firstOrCreate([
                'name' => $equipo->value,
                'institucion_id' => $institucion->id,
                'guard_name' => self::GUARD,
            ]);

            foreach (self::MATRIZ[$equipo->value] as $moduloValue => $acciones) {
                $modulo = Modulo::from($moduloValue);

                $rol->givePermissionTo(array_map(
                    fn (AccionPermiso $accion): Permission => $this->permiso($modulo, $accion),
                    $acciones,
                ));
            }
        }
    }

    /**
     * Matricula en la institución a todos los técnicos provinciales que ya
     * existen, dándoles ahí el rol de su equipo. La guía (cap. 2, pág. 15,
     * línea 433) describe a estos equipos "realizando recorridos
     * institucionales por todos los espacios": se asume que cubren cada EPI
     * de la provincia, presente y futura.
     */
    public function sincronizarTecnicos(Institucion $institucion): void
    {
        User::query()
            ->whereNotNull('equipo_tecnico_provincial')
            ->each(fn (User $tecnico) => $this->enrolarTecnico($tecnico, $institucion));
    }

    public function enrolarTecnico(User $tecnico, Institucion $institucion): void
    {
        $equipo = $tecnico->equipo_tecnico_provincial;

        if ($equipo === null) {
            return;
        }

        $institucion->usuarios()->syncWithoutDetaching([$tecnico->id]);

        // assignRole() resuelve el equipo (team) contra la institución
        // activa de la sesión (ver InstitucionTeamResolver), no contra un
        // parámetro propio. Se guarda y restaura ese valor para no alterar
        // la institución activa de quien esté usando la sesión actual.
        $contexto = app(InstitucionContext::class);
        $institucionActivaPrevia = $contexto->id();

        try {
            $contexto->set($institucion->id);
            $tecnico->assignRole($equipo->value);
        } finally {
            $contexto->set($institucionActivaPrevia);
        }
    }

    /**
     * Crea, si todavía no existe, todo el catálogo de permisos módulo.acción
     * que aparece en la matriz. Comparte el catálogo de permisos con
     * ProvisionadorDeRolesInstitucionales: ambos crean permisos
     * "modulo.accion" con firstOrCreate, así que da igual cuál corra primero.
     */
    public function sembrarCatalogoDePermisos(): void
    {
        foreach (self::MATRIZ as $modulosPorEquipo) {
            foreach ($modulosPorEquipo as $moduloValue => $acciones) {
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
