<?php

namespace App\Models;

use App\Enums\EquipoTecnicoProvincial;
use App\Models\Concerns\AuditaCambios;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_superadmin
 * @property EquipoTecnicoProvincial|null $equipo_tecnico_provincial
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
// "equipo_tecnico_provincial" no es mass-assignable por la misma razón que
// is_superadmin: otorga acceso transversal a instituciones y no debe poder
// fijarse desde un formulario genérico (ver ADR-003).
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements Auditable, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use AuditaCambios, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Atributos que nunca se copian a la auditoría: credenciales y secretos.
     *
     * @var list<string>
     */
    protected array $auditExclude = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'equipo_tecnico_provincial' => EquipoTecnicoProvincial::class,
        ];
    }

    /**
     * @return BelongsToMany<Institucion, $this>
     */
    public function instituciones(): BelongsToMany
    {
        return $this->belongsToMany(Institucion::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
