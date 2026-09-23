<?php

namespace App\Models;

use App\Enums\DiaSemana;
use App\Enums\MomentoComida;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\ItemDeMenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Lo que se sirve en un día y momento de alimentación de un menú semanal.
 *
 * @property int $id
 * @property int $institucion_id
 * @property int $menu_semanal_id
 * @property DiaSemana $dia
 * @property MomentoComida $comida
 * @property ?string $descripcion
 */
class ItemDeMenu extends Model implements Auditable
{
    /** @use HasFactory<ItemDeMenuFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'items_de_menu';

    protected $fillable = ['menu_semanal_id', 'dia', 'comida', 'descripcion'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia' => DiaSemana::class,
            'comida' => MomentoComida::class,
        ];
    }

    /**
     * @return BelongsTo<MenuSemanal, $this>
     */
    public function menuSemanal(): BelongsTo
    {
        return $this->belongsTo(MenuSemanal::class);
    }
}
