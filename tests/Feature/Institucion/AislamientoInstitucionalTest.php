<?php

use App\Models\Concerns\PerteneceAInstitucion;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitucionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo de prueba: representa cualquier entidad de negocio futura (paciente,
 * notificación, etc.) que use el trait PerteneceAInstitucion. No es parte del
 * dominio real todavía, solo existe para probar el mecanismo de aislamiento.
 */
class RegistroDePrueba extends Model
{
    use PerteneceAInstitucion;

    protected $table = 'registros_de_prueba';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('registros_de_prueba', function ($table) {
        $table->id();
        $table->foreignId('institucion_id');
        $table->string('titulo');
        $table->timestamps();
    });

    $this->epiA = Institucion::create(['nombre' => 'EPI A']);
    $this->epiB = Institucion::create(['nombre' => 'EPI B']);

    $this->usuarioA = User::factory()->create();
    $this->usuarioA->instituciones()->attach($this->epiA);

    // Datos de ambas instituciones, insertados sin pasar por el scope
    // (representa datos que ya existen en la base compartida).
    RegistroDePrueba::withoutGlobalScopes()->create([
        'institucion_id' => $this->epiA->id,
        'titulo' => 'Dato de EPI A',
    ]);

    $this->registroB = RegistroDePrueba::withoutGlobalScopes()->create([
        'institucion_id' => $this->epiB->id,
        'titulo' => 'Dato de EPI B',
    ]);
});

afterEach(function () {
    Schema::dropIfExists('registros_de_prueba');
});

it('solo muestra en el listado los registros de la institución activa', function () {
    app(InstitucionContext::class)->set($this->epiA->id);

    expect(RegistroDePrueba::pluck('titulo')->all())->toBe(['Dato de EPI A']);
});

it('no permite acceder por id a un registro de otra institución', function () {
    app(InstitucionContext::class)->set($this->epiA->id);

    expect(RegistroDePrueba::find($this->registroB->id))->toBeNull();
});

it('lanza 404 (no una excepción distinta) al pedir por id un registro de otra institución vía route-model-binding', function () {
    // findOrFail() es lo que Laravel dispara en el binding implícito de rutas
    // (Route::get('/registros/{registro}', ...)): es el vector real cuando
    // alguien cambia el id en la URL, no find() a secas.
    app(InstitucionContext::class)->set($this->epiA->id);

    expect(fn () => RegistroDePrueba::findOrFail($this->registroB->id))
        ->toThrow(ModelNotFoundException::class);
});

it('no muestra nada si no hay institución activa (fail-closed)', function () {
    app(InstitucionContext::class)->set(null);

    expect(RegistroDePrueba::count())->toBe(0);
});

it('el bypass de Gate del superadmin no evita el fail-closed de InstitucionScope', function () {
    $superadmin = User::factory()->create(['is_superadmin' => true]);
    $this->actingAs($superadmin);

    app(InstitucionContext::class)->set(null);
    expect(RegistroDePrueba::count())->toBe(0);

    app(InstitucionContext::class)->set($this->epiA->id);
    expect(RegistroDePrueba::pluck('titulo')->all())->toBe(['Dato de EPI A']);
});
