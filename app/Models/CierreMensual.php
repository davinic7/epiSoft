<?php

namespace App\Models;

use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\CierreMensualFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * El cierre de un período mensual de economato bloquea nuevos movimientos
 * con fecha en ese mes hasta que se reabra (guía, cap. 3.4). No se edita:
 * reabrir un período cerrado dos veces seguidas no tiene sentido de
 * negocio, así que solo hay dos transiciones posibles sobre esta misma
 * fila (cerrar, después reabrir); volver a cerrar el mismo período crea
 * una fila nueva en vez de reescribir esta (ver estaCerradoParaFecha()).
 *
 * @property int $id
 * @property int $institucion_id
 * @property Carbon $periodo
 * @property int $cerrado_por_id
 * @property Carbon $cerrado_en
 * @property ?int $reabierto_por_id
 * @property ?Carbon $reabierto_en
 */
class CierreMensual extends Model implements Auditable
{
    /** @use HasFactory<CierreMensualFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'cierres_mensuales';

    protected $fillable = ['periodo', 'cerrado_por_id', 'cerrado_en', 'reabierto_por_id', 'reabierto_en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'cerrado_en' => 'datetime',
            'reabierto_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reabiertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reabierto_por_id');
    }

    public function reabrir(User $usuario): void
    {
        $this->update([
            'reabierto_por_id' => $usuario->id,
            'reabierto_en' => Carbon::now(),
        ]);
    }

    /**
     * Si hay movimientos de stock con fecha dentro del mes de $fecha,
     * quedan bloqueados. Mira el cierre más reciente de ese mes: si el más
     * reciente sigue sin reabrirse, el período está cerrado.
     */
    public static function estaCerradoParaFecha(Carbon $fecha): bool
    {
        $ultimoCierre = static::query()
            ->whereDate('periodo', $fecha->copy()->startOfMonth()->format('Y-m-d'))
            ->latest('cerrado_en')
            ->first();

        return $ultimoCierre !== null && $ultimoCierre->reabierto_en === null;
    }
}
