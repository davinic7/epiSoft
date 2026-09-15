<?php

namespace Database\Seeders;

use App\Services\ProvisionadorDeRolesInstitucionales;
use App\Services\ProvisionadorDeRolesProvinciales;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Siembra el catálogo de permisos módulo.acción. Los roles en sí se
     * crean por institución (ver App\Observers\InstitucionObserver), no acá.
     */
    public function run(
        ProvisionadorDeRolesInstitucionales $provisionadorInstitucional,
        ProvisionadorDeRolesProvinciales $provisionadorProvincial,
    ): void {
        $provisionadorInstitucional->sembrarCatalogoDePermisos();
        $provisionadorProvincial->sembrarCatalogoDePermisos();
    }
}
