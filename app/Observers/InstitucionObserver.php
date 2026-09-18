<?php

namespace App\Observers;

use App\Models\Institucion;
use App\Services\ProvisionadorDeRolesInstitucionales;
use App\Services\ProvisionadorDeRolesProvinciales;

class InstitucionObserver
{
    public function __construct(
        private readonly ProvisionadorDeRolesInstitucionales $provisionadorInstitucional,
        private readonly ProvisionadorDeRolesProvinciales $provisionadorProvincial,
    ) {}

    /**
     * Siembra los roles institucionales y provinciales estándar (y sus
     * permisos) apenas se crea la institución, para que ninguna alta quede
     * sin roles asignables. También matricula ahí a los técnicos
     * provinciales que ya existen: la guía los describe recorriendo todos
     * los espacios (ver ADR-003).
     */
    public function created(Institucion $institucion): void
    {
        $this->provisionadorInstitucional->provisionar($institucion);
        $this->provisionadorProvincial->provisionar($institucion);
        $this->provisionadorProvincial->sincronizarTecnicos($institucion);
    }
}
