<?php

namespace App\Enums;

/**
 * La entrevista integral distingue alergias ("¿Tiene alguna alergia?
 * ¿Cuál?") de intolerancias/restricciones alimentarias ("¿Tiene
 * intolerancia a algún alimento? ¿A cuál?") como preguntas separadas; este
 * enum conserva esa distinción porque el economato las trata distinto (una
 * alergia puede ser grave y no alimentaria, una restricción siempre es
 * sobre comida).
 */
enum TipoAlergia: string
{
    case Alergia = 'alergia';
    case RestriccionAlimentaria = 'restricción alimentaria';
}
