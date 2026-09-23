<?php

namespace App\Livewire\Ninos;

use App\Enums\AccionPermiso;
use App\Enums\Modulo;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Carga masiva de legajos desde una planilla CSV: cada fila se valida por
 * separado, así que una fila con errores no impide importar el resto (ver
 * criterio "Reporte de filas rechazadas con el motivo").
 *
 * Solo CSV, no .xlsx: el proyecto no tiene una librería de lectura de
 * Excel entre sus dependencias y agregar una requiere aprobación (ver
 * CLAUDE.md, "no cambiar dependencias sin aprobación"). Cualquier planilla
 * real se puede exportar a CSV desde Excel, Google Sheets o LibreOffice.
 *
 * Repetible sin duplicar: una fila cuyo DNI ya existe en la institución
 * (activo o dado de baja) se rechaza en vez de crear un duplicado, así que
 * reimportar el mismo archivo (o uno con filas superpuestas) es seguro.
 */
#[Title('Importar niños')]
class Importar extends Component
{
    use AuthorizesRequests, WithFileUploads;

    /**
     * Columnas esperadas en la primera fila de la planilla, en cualquier
     * orden (no distingue mayúsculas). "sala" y "alias" y
     * "lugar_nacimiento" son opcionales, el resto son obligatorias.
     */
    private const array COLUMNAS = [
        'nombres',
        'apellidos',
        'alias',
        'dni',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'domicilio',
        'fecha_ingreso',
        'sala',
    ];

    private const array COLUMNAS_OPCIONALES = ['alias', 'lugar_nacimiento', 'sala'];

    public ?UploadedFile $archivo = null;

    /**
     * @var array<int, array{fila: int, estado: string, motivo: ?string, nombre: string}>|null
     */
    public ?array $resultado = null;

    public function mount(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Crear));
    }

    public function descargarPlantilla(): StreamedResponse
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Crear));

        return Response::streamDownload(function () {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            fputcsv($salida, self::COLUMNAS);
            fputcsv($salida, [
                'Juana', 'Pérez', 'Juani', '45123456', '2023-05-10',
                'San Fernando del Valle', 'Calle Falsa 123', '2026-01-15', 'Sala de bebés',
            ]);
            fclose($salida);
        }, 'plantilla-ninos.csv', ['Content-Type' => 'text/csv']);
    }

    public function importar(): void
    {
        $this->authorize(Modulo::Ninos->permiso(AccionPermiso::Crear));

        $this->validate(['archivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $filas = $this->leerFilas($this->archivo->getRealPath());

        if ($filas === null) {
            $this->addError('archivo', __('Encabezados esperados: :columnas', ['columnas' => implode(', ', self::COLUMNAS)]));

            return;
        }

        $institucionId = app(InstitucionContext::class)->id();
        $salaPorNombre = Sala::query()->get()->keyBy(fn (Sala $sala) => mb_strtolower($sala->nombre));

        $dnisEnArchivo = [];
        $resultado = [];

        foreach ($filas as $numeroFila => $datos) {
            $nombreCompleto = trim(($datos['nombres'] ?? '').' '.($datos['apellidos'] ?? ''));
            $errores = $this->validarFila($datos, $institucionId, $salaPorNombre, $dnisEnArchivo);

            if ($errores !== []) {
                $resultado[] = [
                    'fila' => $numeroFila,
                    'estado' => 'rechazada',
                    'motivo' => implode(' ', $errores),
                    'nombre' => $nombreCompleto ?: __('(sin nombre)'),
                ];

                continue;
            }

            $dni = trim($datos['dni']);
            $dnisEnArchivo[] = $dni;

            Nino::create([
                'nombres' => trim($datos['nombres']),
                'apellidos' => trim($datos['apellidos']),
                'alias' => trim((string) ($datos['alias'] ?? '')) ?: null,
                'dni' => $dni,
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'lugar_nacimiento' => trim((string) ($datos['lugar_nacimiento'] ?? '')) ?: null,
                'domicilio' => trim($datos['domicilio']),
                'fecha_ingreso' => $datos['fecha_ingreso'],
                'sala_id' => $salaPorNombre->get(mb_strtolower(trim((string) ($datos['sala'] ?? ''))))?->id,
            ]);

            $resultado[] = [
                'fila' => $numeroFila,
                'estado' => 'importada',
                'motivo' => null,
                'nombre' => $nombreCompleto,
            ];
        }

        $this->resultado = $resultado;
        $this->archivo = null;
    }

    /**
     * Lee la planilla y la agrupa por nombre de columna. Devuelve null si
     * el encabezado no tiene las columnas obligatorias.
     *
     * @return array<int, array<string, string>>|null
     */
    private function leerFilas(string $ruta): ?array
    {
        $manija = fopen($ruta, 'r');

        if ($manija === false) {
            return null;
        }

        $encabezado = fgetcsv($manija);

        if ($encabezado === false) {
            fclose($manija);

            return null;
        }

        // Excel suele anteponer un BOM UTF-8 a la primera celda.
        $encabezado[0] = preg_replace('/^\x{FEFF}/u', '', (string) $encabezado[0]);
        $encabezado = array_map(fn ($columna) => mb_strtolower(trim((string) $columna)), $encabezado);

        $obligatorias = array_diff(self::COLUMNAS, self::COLUMNAS_OPCIONALES);
        if (array_diff($obligatorias, $encabezado) !== []) {
            fclose($manija);

            return null;
        }

        $filas = [];
        $numeroFila = 1;

        while (($linea = fgetcsv($manija)) !== false) {
            $numeroFila++;

            if ($linea === [null] || $linea === ['']) {
                continue;
            }

            $fila = [];
            foreach ($encabezado as $indice => $columna) {
                if (in_array($columna, self::COLUMNAS, true)) {
                    $fila[$columna] = $linea[$indice] ?? '';
                }
            }

            $filas[$numeroFila] = $fila;
        }

        fclose($manija);

        return $filas;
    }

    /**
     * @param  array<string, string>  $datos
     * @param  Collection<string, Sala>  $salaPorNombre
     * @param  list<string>  $dnisEnArchivo
     * @return list<string>
     */
    private function validarFila(array $datos, ?int $institucionId, Collection $salaPorNombre, array $dnisEnArchivo): array
    {
        $errores = [];

        foreach (['nombres', 'apellidos', 'dni', 'fecha_nacimiento', 'domicilio', 'fecha_ingreso'] as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                $errores[] = __('Falta :campo.', ['campo' => $campo]);
            }
        }

        if ($errores !== []) {
            return $errores;
        }

        $dni = trim($datos['dni']);

        if (in_array($dni, $dnisEnArchivo, true)) {
            $errores[] = __('DNI repetido dentro de la planilla.');
        } elseif (Nino::withTrashed()->where('institucion_id', $institucionId)->where('dni', $dni)->exists()) {
            $errores[] = __('Ya existe un niño con ese DNI en la institución.');
        }

        foreach (['fecha_nacimiento', 'fecha_ingreso'] as $campoFecha) {
            if (! $this->esFechaValida($datos[$campoFecha])) {
                $errores[] = __('El :campo debe tener formato AAAA-MM-DD.', ['campo' => $campoFecha]);
            }
        }

        $sala = trim((string) ($datos['sala'] ?? ''));
        if ($sala !== '' && ! $salaPorNombre->has(mb_strtolower($sala))) {
            $errores[] = __('La sala ":sala" no existe.', ['sala' => $sala]);
        }

        return $errores;
    }

    private function esFechaValida(string $valor): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($valor), $partes) !== 1) {
            return false;
        }

        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
    }

    public function render(): View
    {
        return view('livewire.ninos.importar');
    }
}
