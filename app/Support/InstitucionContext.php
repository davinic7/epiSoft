<?php

namespace App\Support;

/**
 * Resuelve la institución activa de la sesión actual. Es la única fuente de
 * verdad que consulta InstitucionScope para decidir qué fila puede ver cada
 * request — no hay otro camino para leer datos de negocio sin pasar por acá.
 */
class InstitucionContext
{
    public function id(): ?int
    {
        return session('institucion_id');
    }

    public function set(?int $institucionId): void
    {
        session(['institucion_id' => $institucionId]);
    }
}
