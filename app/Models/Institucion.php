<?php

namespace App\Models;

use App\Observers\InstitucionObserver;
use Database\Factories\InstitucionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(InstitucionObserver::class)]
class Institucion extends Model
{
    /** @use HasFactory<InstitucionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'instituciones';

    protected $fillable = ['nombre', 'direccion', 'cuit', 'referente', 'capacidad'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
