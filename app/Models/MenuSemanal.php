<?php

namespace App\Models;

use App\Enums\EstadoMenu;
use App\Models\Concerns\AuditaCambios;
use App\Models\Concerns\PerteneceAInstitucion;
use Database\Factories\MenuSemanalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * El menú semanal de una institución, con el circuito de aprobación del
 * equipo técnico de nutrición (guía, cap. 2, pág. 16: "implementación de
 * menúes estacionales... recomendaciones nutricionales"; ver
 * App\Enums\EquipoTecnicoProvincial::Nutricion y
 * ProvisionadorDeRolesProvinciales). Una vez aprobado no se edita.
 *
 * @property int $id
 * @property int $institucion_id
 * @property Carbon $semana_inicio
 * @property EstadoMenu $estado
 * @property ?int $aprobado_por_id
 * @property ?Carbon $aprobado_en
 */
class MenuSemanal extends Model implements Auditable
{
    /** @use HasFactory<MenuSemanalFactory> */
    use AuditaCambios, HasFactory, PerteneceAInstitucion;

    protected $table = 'menus_semanales';

    protected $fillable = ['semana_inicio'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semana_inicio' => 'date',
            'estado' => EstadoMenu::class,
            'aprobado_en' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ItemDeMenu, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemDeMenu::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por_id');
    }

    public function aprobar(User $usuario): void
    {
        $this->estado = EstadoMenu::Aprobado;
        $this->aprobado_por_id = $usuario->id;
        $this->aprobado_en = Carbon::now();
        $this->save();
    }
}
