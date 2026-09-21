<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Vínculo entre un niño y un referente afectivo: el parentesco y la
 * autorización de retiro son propios de esa pareja niño-referente, no de
 * ninguno de los dos por separado (ver Nino::referentes()).
 *
 * @property string $parentesco
 * @property bool $autorizado_a_retirar
 */
class NinoReferente extends Pivot
{
    protected $table = 'nino_referente';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'autorizado_a_retirar' => 'boolean',
        ];
    }
}
