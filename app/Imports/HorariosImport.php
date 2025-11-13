<?php

namespace App\Imports;

use App\Models\Horario;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class HorariosImport implements ToCollection, WithHeadingRow
{
    public function __construct(private int $importId, private int $userId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $dmg = (int)($row['id_docente_materia_gestion'] ?? 0);
                $grupo = (int)($row['id_grupo'] ?? 0);
                if ($dmg <= 0 || $grupo <= 0) { $this->tick(); continue; }
                $dia = $this->normalizeDia((string)($row['dia'] ?? ''));
                $hi = $row['hora_inicio'] ?? null;
                $hf = $row['hora_fin'] ?? null;
                if ($dia === null || empty($hi) || empty($hf)) { $this->tick(); continue; }
                $aula = $row['id_aula'] ?? null;
                $modalidad = strtoupper((string)($row['modalidad'] ?? 'PRESENCIAL'));
                if (! in_array($modalidad, ['PRESENCIAL','VIRTUAL','HIBRIDA'])) { $modalidad = 'PRESENCIAL'; }

                $data = [
                    'id_docente_materia_gestion' => $dmg,
                    'id_grupo' => $grupo,
                    'id_aula' => $aula ?: null,
                    'dia' => $dia,
                    'hora_inicio' => $hi,
                    'hora_fin' => $hf,
                    'modalidad' => $modalidad,
                    'virtual_plataforma' => $row['virtual_plataforma'] ?? null,
                    'virtual_enlace' => $row['virtual_enlace'] ?? null,
                    'observacion' => $row['observacion'] ?? null,
                ];

                // Crear. En caso de exclusiones/uniques, el try/catch la registra.
                Horario::create($data);
            } catch (\Throwable $e) {
                DB::table('bitacora')->insert([
                    'id_usuario' => $this->userId,
                    'accion' => 'IMPORTAR_ERROR',
                    'tabla_afectada' => 'horarios',
                    'id_afectado' => (string)($row['id_grupo'] ?? ''),
                    'ip_origen' => request()->ip() ?? 'import',
                    'descripcion' => substr('Error fila: '.$e->getMessage(),0,255),
                    'fecha' => now(),
                ]);
            } finally {
                $this->tick();
            }
        }
    }

    private function tick(): void
    {
        DB::table('importaciones_usuarios')->where('id_importacion', $this->importId)
            ->update(['filas_procesadas' => DB::raw('COALESCE(filas_procesadas,0)+1')]);
    }

    private function normalizeDia(string $v): ?string
    {
        $v = trim(mb_strtolower($v));
        $map = [
            'lunes' => 'Lunes',
            'martes' => 'Martes',
            'miercoles' => 'Miércoles',
            'miércoles' => 'Miércoles',
            'jueves' => 'Jueves',
            'viernes' => 'Viernes',
            'sabado' => 'Sábado',
            'sábado' => 'Sábado',
        ];
        return $map[$v] ?? null;
    }
}

