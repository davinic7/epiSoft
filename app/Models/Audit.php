<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAInstitucion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Models\Audit as AuditBase;

/**
 * Registro de auditoría de una institución (ver config/audit.php). Es de
 * solo escritura: una vez creado no se edita ni se borra desde la
 * aplicación, porque su valor es ser evidencia.
 *
 * Usa PerteneceAInstitucion, así que la consulta queda filtrada por la
 * institución activa (fail-closed) igual que cualquier dato de negocio.
 * institucion_id lo completa Auditable::transformAudit() a partir de la
 * entidad auditada, no del contexto de sesión.
 */
class Audit extends AuditBase
{
    use PerteneceAInstitucion;

    protected static function booted(): void
    {
        static::updating(fn (): bool => false);
        static::deleting(fn (): bool => false);
    }

    /**
     * Nombre en español del evento, para mostrar en pantalla.
     */
    public function etiquetaEvento(): string
    {
        return match ($this->event) {
            'created' => __('Alta'),
            'updated' => __('Modificación'),
            'deleted' => __('Baja'),
            'restored' => __('Restauración'),
            'acceso' => __('Acceso'),
            default => $this->event,
        };
    }

    /**
     * Nombre en español de la entidad auditada, para mostrar en pantalla.
     */
    public function etiquetaEntidad(): string
    {
        return match ($this->auditable_type) {
            Institucion::class => __('Institución'),
            User::class => __('Usuario'),
            Nino::class => __('Niño'),
            default => class_basename($this->auditable_type),
        };
    }

    /**
     * Usuario que hizo la acción. Complementa user() del paquete, que es un
     * morphTo genérico, con una relación tipada hacia App\Models\User.
     *
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
