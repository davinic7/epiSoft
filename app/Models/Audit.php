<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAInstitucion;
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
}
