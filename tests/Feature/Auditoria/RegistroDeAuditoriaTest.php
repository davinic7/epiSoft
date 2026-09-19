<?php

use App\Enums\RolInstitucional;
use App\Livewire\Auditoria\Index;
use App\Models\Audit;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;
use Livewire\Livewire;

// El paquete no audita en consola (db:seed, artisan) y los tests corren ahí.
beforeEach(fn () => config(['audit.console' => true]));

function usuarioConRol(RolInstitucional $rol, Institucion $institucion): User
{
    $usuario = User::factory()->create();

    app(InstitucionContext::class)->set($institucion->id);
    $usuario->assignRole($rol->value);

    return $usuario;
}

it('registra usuario, acción, entidad, fecha e IP al crear una institución', function () {
    $usuario = User::factory()->create(['is_superadmin' => true]);
    request()->server->set('REMOTE_ADDR', '10.1.2.3');

    $this->actingAs($usuario);
    $institucion = Institucion::create(['nombre' => 'EPI A']);

    $registro = Audit::withoutGlobalScopes()->where('auditable_type', Institucion::class)->sole();

    expect($registro->event)->toBe('created')
        ->and($registro->auditable_id)->toBe($institucion->id)
        ->and($registro->user_id)->toBe($usuario->id)
        ->and($registro->ip_address)->toBe('10.1.2.3')
        ->and($registro->institucion_id)->toBe($institucion->id)
        ->and($registro->created_at)->not->toBeNull();
});

it('no copia contraseñas ni secretos a la auditoría de un usuario', function () {
    $usuario = User::factory()->create();

    $usuario->update(['name' => 'Otro nombre', 'password' => 'una-clave-nueva']);

    $registro = Audit::withoutGlobalScopes()
        ->where('auditable_type', User::class)
        ->where('event', 'updated')
        ->sole();

    expect($registro->new_values)->toHaveKey('name')
        ->and($registro->new_values)->not->toHaveKey('password')
        ->and($registro->old_values)->not->toHaveKey('password');
});

it('deja constancia de un acceso de lectura en la institución activa', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($institucion->id);
    $usuario = User::factory()->create();

    $this->actingAs($usuario);
    $usuario->registrarAcceso();

    $registro = Audit::query()->where('event', 'acceso')->sole();

    expect($registro->auditable_id)->toBe($usuario->id)
        ->and($registro->user_id)->toBe($usuario->id)
        ->and($registro->institucion_id)->toBe($institucion->id);
});

it('no permite editar ni borrar un registro de auditoría', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($institucion->id);
    $registro = Audit::query()->sole();

    expect($registro->update(['event' => 'deleted']))->toBeFalse()
        ->and($registro->delete())->toBeFalse()
        ->and(Audit::query()->count())->toBe(1)
        ->and($registro->fresh()->event)->toBe('created');
});

it('el equipo de coordinación consulta solo la auditoría de su institución', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $epiA);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($coordinador)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('Institucion #'.$epiA->id)
        ->assertDontSee('Institucion #'.$epiB->id);
});

it('los demás roles no acceden a la auditoría', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);
    $educador = usuarioConRol(RolInstitucional::Educador, $epi);

    $this->actingAs($educador)
        ->get(route('auditoria.index'))
        ->assertForbidden();
});

it('el superadmin accede a la auditoría', function () {
    $epi = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($epi->id);
    $superadmin = User::factory()->create(['is_superadmin' => true]);

    $this->actingAs($superadmin)
        ->get(route('auditoria.index'))
        ->assertOk();
});
