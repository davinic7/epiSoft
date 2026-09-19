<?php

namespace App\Models\Concerns;

use App\Support\InstitucionContext;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Events\AuditCustom;

/**
 * Registra en auditoría las altas, cambios y bajas del modelo, y permite
 * dejar constancia de un acceso de lectura con registrarAcceso(). Los
 * modelos que la usen deben implementar OwenIt\Auditing\Contracts\Auditable.
 *
 * Todo modelo con datos sensibles de niños tiene que llamar a
 * registrarAcceso() cada vez que un usuario abre o consulta un registro
 * (docs/BACKLOG.md, issue "Registro de auditoría").
 */
trait AuditaCambios
{
    use Auditable;

    /**
     * Institución a la que se asigna el registro de auditoría: la propia
     * fila si guarda institucion_id y, si no, la institución activa.
     */
    protected function institucionParaAuditoria(): ?int
    {
        return $this->getAttribute('institucion_id') ?? app(InstitucionContext::class)->id();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        $data['institucion_id'] = $this->institucionParaAuditoria();

        return $data;
    }

    /**
     * Deja constancia de que el usuario actual accedió a este registro.
     */
    public function registrarAcceso(): void
    {
        $this->auditEvent = 'acceso';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [];
        $this->auditCustomNew = [];

        Event::dispatch(new AuditCustom($this));
    }
}
