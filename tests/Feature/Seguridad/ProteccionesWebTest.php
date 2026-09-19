<?php

use App\Livewire\Auditoria\Index as AuditoriaIndex;
use App\Livewire\Instituciones\Index as InstitucionesIndex;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/**
 * Laravel apaga la protección CSRF cuando corre en tests. Se reemplaza el
 * middleware por una subclase que la deja activa, para probar el
 * comportamiento real del grupo "web".
 */
function activarProteccionCsrf(): void
{
    app()->instance(PreventRequestForgery::class, new class(app(), app('encrypter')) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });
}

/**
 * @return list<string>
 */
function archivosDe(string $directorio, string $extension): array
{
    return collect(File::allFiles($directorio))
        ->filter(fn ($archivo) => str_ends_with($archivo->getFilename(), $extension))
        ->map(fn ($archivo) => $archivo->getPathname())
        ->values()
        ->all();
}

it('rechaza con 419 un POST web sin token CSRF', function () {
    activarProteccionCsrf();

    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertStatus(419);
});

it('rechaza con 419 una actualización de Livewire sin token CSRF', function () {
    activarProteccionCsrf();

    $this->actingAs(User::factory()->create())
        ->postJson(Livewire::getUpdateUri(), ['components' => []])
        ->assertStatus(419);
});

it('acepta un POST web con el token CSRF de la sesión', function () {
    activarProteccionCsrf();

    $this->actingAs(User::factory()->create())
        ->withSession(['_token' => 'token-de-prueba'])
        ->post(route('logout'), ['_token' => 'token-de-prueba'])
        ->assertRedirect();

    $this->assertGuest();
});

it('escapa el HTML de los datos que muestra, en vez de ejecutarlo', function () {
    $payload = '<script>alert("xss")</script>';
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $institucion = Institucion::create(['nombre' => $payload]);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($superadmin)
        ->test(InstitucionesIndex::class)
        ->assertDontSee($payload, false)
        ->assertSee('&lt;script&gt;', false);
});

it('no concatena el filtro de la auditoría en la consulta (inyección SQL)', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    app(InstitucionContext::class)->set($institucion->id);
    config(['audit.console' => true]);
    $institucion->update(['capacidad' => 10]);

    Livewire::actingAs($superadmin)
        ->test(AuditoriaIndex::class)
        ->set('evento', "' OR '1'='1")
        ->assertOk()
        ->assertSee('Todavía no hay registros de auditoría.');
});

it('no usa SQL crudo en el código de la aplicación', function () {
    $patron = '/whereRaw|orderByRaw|selectRaw|havingRaw|groupByRaw|DB::raw|DB::unprepared|DB::statement|DB::select\(|DB::insert\(|DB::update\(|DB::delete\(/';

    $archivos = [...archivosDe(app_path(), '.php'), ...archivosDe(database_path('seeders'), '.php')];

    expect($archivos)->not->toBeEmpty();

    $conSqlCrudo = collect($archivos)
        ->filter(fn (string $ruta) => preg_match($patron, file_get_contents($ruta)) === 1)
        ->map(fn (string $ruta) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $ruta))
        ->values()
        ->all();

    expect($conSqlCrudo)->toBe([]);
});

it('solo imprime HTML sin escapar donde está revisado', function () {
    // El único caso permitido es el QR de doble factor: lo genera Fortify
    // como SVG propio, no contiene datos del usuario. Cualquier otro
    // `{!! !!}` nuevo tiene que pasar por revisión antes de agregarse acá.
    $permitidos = ['livewire'.DIRECTORY_SEPARATOR.'settings'.DIRECTORY_SEPARATOR.'security.blade.php'];

    $vistas = archivosDe(resource_path('views'), '.blade.php');

    expect($vistas)->not->toBeEmpty();

    $sinEscapar = collect($vistas)
        ->filter(fn (string $ruta) => str_contains(file_get_contents($ruta), '{!!'))
        ->reject(fn (string $ruta) => collect($permitidos)->contains(fn (string $ok) => str_ends_with($ruta, $ok)))
        ->all();

    expect($sinEscapar)->toBe([]);
});
