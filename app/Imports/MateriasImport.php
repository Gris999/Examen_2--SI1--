<?php

namespace App\Imports;

use App\Models\Materia;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class MateriasImport implements ToCollection, WithHeadingRow
{
    public function __construct(private int $importId, private int $userId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $idCarrera = (int)($row['id_carrera'] ?? 0);
                $nombre = trim((string)($row['nombre'] ?? ''));
                $codigo = trim((string)($row['codigo'] ?? ''));
                if ($idCarrera <= 0 || $nombre === '' || $codigo === '') { $this->tick(); continue; }

                $carga = (int)($row['carga_horaria'] ?? 0);
                $desc = $row['descripcion'] ?? null;

                Materia::updateOrCreate(
                    ['id_carrera' => $idCarrera, 'codigo' => $codigo],
                    ['nombre' => $nombre, 'carga_horaria' => $carga, 'descripcion' => $desc]
                );
            } catch (\Throwable $e) {
                DB::table('bitacora')->insert([
                    'id_usuario' => $this->userId,
                    'accion' => 'IMPORTAR_ERROR',
                    'tabla_afectada' => 'materias',
                    'id_afectado' => (string)($row['codigo'] ?? ''),
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
}

