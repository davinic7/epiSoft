<?php

use App\Models\Institucion;
use App\Services\ProvisionadorDeRolesInstitucionales;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Las salas pasaron de autorizarse con ninos.* a tener su propio módulo
 * (Modulo::Salas). Los roles de las instituciones que ya existían se
 * crearon con la matriz anterior, así que se vuelven a provisionar para
 * que reciban los permisos salas.*. Provisionar solo agrega permisos, no
 * quita ninguno.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $provisionador = app(ProvisionadorDeRolesInstitucionales::class);

        Institucion::withTrashed()->each(fn (Institucion $institucion) => $provisionador->provisionar($institucion));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
