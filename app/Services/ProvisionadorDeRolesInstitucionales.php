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
     * módulo ausente (o con lista vacía) para un rol significa que ese rol
     * no tiene ningún permiso ahí.
     *
     * Cada fila cita la sección de docs/guia-buenas-practicas-epi.md de la
     * que surge la responsabilidad; donde el módulo es una construcción de
     * la aplicación sin equivalente textual en la guía (Usuarios, Alertas),
     * el comentario lo aclara en vez de inventar una cita. De los roles
     * institucionales, solo el equipo de coordinación tiene acceso a
     * Modulo::Vulneraciones (los equipos técnicos provinciales de abordaje
     * global y trabajo social también lo tienen, ver
     * ProvisionadorDeRolesProvinciales::MATRIZ y ADR-003). Ningún rol del
     * sistema tiene Eliminar ahí (ver guía, cap. 6.1, y docs/backlog.txt,
     * issue "Módulo de vulneración de derechos").
     *
     * @var array<string, array<string, list<AccionPermiso>>>
     */
    private const array MATRIZ = [
        RolInstitucional::EquipoCoordinacion->value => [
            // "Organizar los roles y tareas institucionales que hacen al
            // funcionamiento del EPI" (guía, cap. 2, pág. 10-12). Sin cita
            // textual para "cuentas de sistema": se infiere de esa función
            // organizativa, no hay equivalente literal en la guía.
            Modulo::Usuarios->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            // Entrevistas integrales, conformación de grupos y legajos;
            // procedimientos de alta, baja y pase institucional (guía,
            // cap. 2, pág. 10-12; cap. 4.2, pág. 27-29).
            Modulo::Ninos->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            // Conformación de grupos (guía, cap. 2, pág. 10-12): arma las
            // salas y asigna a los niños junto al coordinador pedagógico.
            Modulo::Salas->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            // Responsable del libro de entrada/salida y del libro de
            // economato ante la ausencia del encargado de economato (guía,
            // cap. 3.4 "Documentación institucional general", pág. 23).
            Modulo::Economato->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // "Realizar el seguimiento de las propuestas y actividades
            // planificadas" y "lectura y comentarios de planificaciones":
            // supervisión, no autoría (guía, cap. 2, pág. 10-12).
            Modulo::Pedagogico->value => [AccionPermiso::Ver, AccionPermiso::Editar],
            // Organiza horarios institucionales y complementa cargas
            // horarias del personal (guía, cap. 2, pág. 10-12).
            Modulo::Rrhh->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            // Uso y mapa de riesgo de los espacios (cap. 2 y cap. 5.3, pág.
            // 35-36), articulaciones institucionales (cap. 2, pág. 10-12) e
            // informe de gestión institucional (cap. 3.5, pág. 24).
            Modulo::Institucional->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar, AccionPermiso::Eliminar],
            // Actor central de todo el proceso ante sospecha de violencia
            // contra niñas y niños: recibe la comunicación, activa la
            // observación, redacta el informe y eleva la comunicación
            // institucional (guía, cap. 6.1, pág. 40-45). Sin Eliminar: el
            // registro no se borra (ver docs/backlog.txt, issue "Módulo de
            // vulneración de derechos").
            Modulo::Vulneraciones->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // Sin equivalente textual en la guía: responsable general de
            // actuar sobre los vencimientos y riesgos que el resto de los
            // módulos deriva.
            Modulo::Alertas->value => [AccionPermiso::Ver, AccionPermiso::Editar],
            // Sin equivalente textual en la guía: la consulta de auditoría
            // la pide el backlog (issue "Registro de auditoría") para el
            // equipo de coordinación y el superadmin. Solo lectura: el
            // registro no se edita ni se borra.
            Modulo::Auditoria->value => [AccionPermiso::Ver],
        ],
        RolInstitucional::EncargadoRecepcion->value => [
            // Conoce a los referentes autorizados a retirar a cada niño y
            // registra por escrito toda novedad, incluido el horario de
            // ingreso y egreso (guía, cap. 2.2 y Anexo 1, Ficha 1, pág. 13,
            // 58). No edita el legajo: eso es tarea de coordinación y
            // educadoras.
            Modulo::Ninos->value => [AccionPermiso::Ver, AccionPermiso::Crear],
            // Toma la asistencia diaria agrupada por sala (docs/backlog.txt,
            // issue "Asistencia diaria de niños"): ve las salas, no las arma.
            Modulo::Salas->value => [AccionPermiso::Ver],
        ],
        RolInstitucional::CoordinadorPedagogico->value => [
            // Necesita ver el legajo para la valoración preliminar ante
            // desafíos en el desarrollo (guía, cap. 6.2, pág. 46-51).
            Modulo::Ninos->value => [AccionPermiso::Ver],
            // Coordina a las educadoras de cada sala y arma las salas y la
            // asignación de niños junto al equipo de coordinación
            // (docs/backlog.txt, issue "Salas con descripción y asignación
            // de niños"). Dar de baja una sala queda para coordinación.
            Modulo::Salas->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // Acompaña la elaboración de planificaciones e informes de las
            // educadoras, y diseña el plan de acompañamiento institucional
            // (guía, cap. 2.2 y Anexo 1, Ficha 2, pág. 13, 59; cap. 6.2).
            Modulo::Pedagogico->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
        ],
        RolInstitucional::Educador->value => [
            // Detecta, registra e informa indicadores de vulneración o de
            // desafíos en el desarrollo; asienta observaciones en el libro
            // de registro diario e informes de seguimiento (guía, cap. 2.2
            // y Anexo 1, Ficha 3, pág. 14, 60; Anexo 8, pág. 83).
            Modulo::Ninos->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
            // Ve las salas y quiénes están en cada una; no las arma
            // (docs/backlog.txt, issue "Educadoras por sala").
            Modulo::Salas->value => [AccionPermiso::Ver],
            // Planifica e implementa propuestas lúdico-pedagógicas y lleva
            // el libro de registro diario por sala (guía, cap. 2.2 y Anexo
            // 1, Ficha 3, pág. 14, 60).
            Modulo::Pedagogico->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
        ],
        RolInstitucional::EncargadoEconomato->value => [
            // Suministra alimentos, gestiona insumos y lleva el libro de
            // entrada/salida, de inventario y de economato (guía, cap. 2.2
            // y Anexo 1, Ficha 4, pág. 15, 60; cap. 3.4, pág. 23).
            Modulo::Economato->value => [AccionPermiso::Ver, AccionPermiso::Crear, AccionPermiso::Editar],
        ],
        RolInstitucional::PersonalCocina->value => [
            // Ejecuta el menú planificado por el equipo de nutrición; no
            // gestiona stock ni insumos (guía, cap. 2.2 y Anexo 1, Ficha 5,
            // pág. 15, 61).
            Modulo::Economato->value => [AccionPermiso::Ver],
        ],
        // Personal de mantenimiento y limpieza: sin permisos por ahora. La
        // guía (cap. 2.2 y Anexo 1, Ficha 6, pág. 15, 62) describe solo
        // tareas físicas sobre el espacio; el registro del mapa de riesgo
        // es responsabilidad del equipo de coordinación (cap. 5.3, pág.
        // 35-36), no de este rol.
        RolInstitucional::PersonalMantenimiento->value => [],
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
