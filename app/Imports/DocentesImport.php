<?php

namespace App\Imports;

use App\Models\Usuario;
use App\Models\Docente;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class DocentesImport implements ToCollection, WithHeadingRow
{
    public function __construct(private int $importId, private int $userId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $correo = trim((string)($row['correo'] ?? ''));
                if ($correo === '') { $this->tick(); continue; }

                $usuario = Usuario::firstOrNew(['correo' => $correo]);
                $usuario->nombre = (string)($row['nombre'] ?? $usuario->nombre);
                $usuario->apellido = (string)($row['apellido'] ?? $usuario->apellido);
                $usuario->telefono = $row['telefono'] ?? $usuario->telefono;
                if (! $usuario->exists) {
                    $usuario->contrasena = 'secret123';
                    $usuario->activo = true;
                }
                $usuario->save();

                // Asegurar rol DOCENTE
                $rolId = DB::table('roles')->whereRaw('LOWER(nombre)=LOWER(?)', ['DOCENTE'])->value('id_rol');
                if ($rolId) {
                    $exists = DB::table('usuario_rol')
                        ->where('id_usuario', $usuario->id_usuario)
                        ->where('id_rol', $rolId)->exists();
                    if (! $exists) {
                        DB::table('usuario_rol')->insert([
                            'id_usuario' => $usuario->id_usuario,
                            'id_rol' => $rolId,
                        ]);
                    }
                }

                // Docente perfil
                Docente::updateOrCreate(
                    ['id_usuario' => $usuario->id_usuario],
                    [
                        'codigo_docente' => $row['codigo_docente'] ?? ( $row['codigo'] ?? ( 'DOC-'.$usuario->id_usuario ) ),
                        'profesion' => $row['profesion'] ?? null,
                        'grado_academico' => $row['grado_academico'] ?? null,
                    ]
                );
            } catch (\Throwable $e) {
                // Registrar error en bitácora por fila fallida
                DB::table('bitacora')->insert([
                    'id_usuario' => $this->userId,
                    'accion' => 'IMPORTAR_ERROR',
                    'tabla_afectada' => 'docentes',
                    'id_afectado' => (string)($row['correo'] ?? ''),
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

