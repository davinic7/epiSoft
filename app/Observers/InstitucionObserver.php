<?php

namespace App\Observers;

use App\Models\Institucion;
use App\Services\ProvisionadorDeRolesInstitucionales;

class InstitucionObserver
{
    public function __construct(private readonly ProvisionadorDeRolesInstitucionales $provisionador) {}

    /**
     * Siembra los roles institucionales estándar (y sus permisos) apenas se
     * crea la institución, para que ninguna alta quede sin roles asignables.
     */
    public function created(Institucion $institucion): void
    {
        $this->provisionador->provisionar($institucion);
    }
}
