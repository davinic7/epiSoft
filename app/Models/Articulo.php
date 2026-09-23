<?php

namespace App\Models;

use App\Enums\CategoriaArticulo;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\ArticuloFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $institucion_id
 * @property string $nombre
 * @property CategoriaArticulo $categoria
 * @property string $unidad_medida
 * @property string $stock_minimo
 */
class Articulo extends Model implements Auditable
{
    /** @use HasFactory<ArticuloFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $fillable = ['nombre', 'categoria', 'unidad_medida', 'stock_minimo'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => CategoriaArticulo::class,
            'stock_minimo' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Lote, $this>
     */
    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }
}
