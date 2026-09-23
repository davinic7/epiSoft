<?php

namespace App\Models;

use App\Enums\EstadoConservacion;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\BienFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Un bien mueble del inventario patrimonial (guía, cap. 3.4, "Libro de
 * registro de inventario institucional", pág. 24: "nombre, código,
 * ubicación y estado"). La ubicación no se edita directamente: cambiarla
 * pasa por moverA(), que además deja constancia en movimientosDeUbicacion.
 *
 * @property int $id
 * @property int $institucion_id
 * @property string $nombre
 * @property string $codigo
 * @property string $ubicacion
 * @property EstadoConservacion $estado_conservacion
 */
class Bien extends Model implements Auditable
{
    /** @use HasFactory<BienFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'bienes';

    protected $fillable = ['nombre', 'codigo', 'ubicacion', 'estado_conservacion'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_conservacion' => EstadoConservacion::class,
        ];
    }

    /**
     * @return HasMany<MovimientoDeUbicacion, $this>
     */
    public function movimientosDeUbicacion(): HasMany
    {
        return $this->hasMany(MovimientoDeUbicacion::class);
    }

    public function moverA(string $ubicacionNueva, Carbon $fecha): void
    {
        $this->movimientosDeUbicacion()->create([
            'ubicacion_anterior' => $this->ubicacion,
            'ubicacion_nueva' => $ubicacionNueva,
            'fecha' => $fecha,
        ]);

        $this->update(['ubicacion' => $ubicacionNueva]);
    }
}
