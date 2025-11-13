<?php

namespace App\Http\Controllers;

use App\Imports\DocentesImport;
use App\Imports\MateriasImport;
use App\Imports\HorariosImport;
use App\Models\ImportacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportacionController extends Controller
{
    private function ensureAuthorized(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles);
        abort_unless($ok, 403);
    }

    public function index()
    {
        $this->ensureAuthorized();
        $imports = ImportacionUsuario::orderBy('fecha','desc')->orderBy('id_importacion','desc')->paginate(20);
        return view('importaciones.index', compact('imports'));
    }

    public function create()
    {
        $this->ensureAuthorized();
        $supportsExcel = class_exists(\Maatwebsite\Excel\Excel::class);
        return view('importaciones.create', compact('supportsExcel'));
    }

    public function store(Request $request)
    {
        $this->ensureAuthorized();
        $validated = $request->validate([
            'tipo' => 'required|in:docentes,materias,horarios',
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if (!class_exists(\Maatwebsite\Excel\Excel::class)) {
            return back()->withErrors(['archivo' => 'Paquete Maatwebsite/Excel no instalado.'])->withInput();
        }

        $file = $request->file('archivo');
        $origName = $file->getClientOriginalName();

        // Crear registro de importación en PROCESANDO
        $imp = ImportacionUsuario::create([
            'archivo_nombre' => $origName,
            'total_filas' => 0,
            'filas_procesadas' => 0,
            'fecha' => now(),
            'usuario_ejecutor' => auth()->user()->id_usuario ?? null,
            'estado' => 'PROCESANDO',
        ]);

        // Determinar total de filas (leyendo a colección)
        $total = 0;
        try {
            $collection = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow {
                public function collection(\Illuminate\Support\Collection $rows) {}
            }, $file);
            if ($collection->count() > 0) {
                $total = $collection[0]->count();
            }
        } catch (\Throwable $e) {
            // continuar con total 0
        }
        $imp->total_filas = $total;
        $imp->save();

        // Ejecutar import correspondiente
        $errors = 0;
        try {
            $tipo = $validated['tipo'];
            if ($tipo === 'docentes') {
                Excel::import(new DocentesImport($imp->id_importacion, auth()->user()->id_usuario ?? 0), $file);
                $tabla = 'docentes';
            } elseif ($tipo === 'materias') {
                Excel::import(new MateriasImport($imp->id_importacion, auth()->user()->id_usuario ?? 0), $file);
                $tabla = 'materias';
            } else {
                Excel::import(new HorariosImport($imp->id_importacion, auth()->user()->id_usuario ?? 0), $file);
                $tabla = 'horarios';
            }

            $imp->estado = 'COMPLETADO';
            $imp->save();

            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => 'IMPORTAR',
                'tabla_afectada' => $tabla,
                'id_afectado' => (string)$imp->id_importacion,
                'ip_origen' => $request->ip(),
                'descripcion' => 'Importación completada: '.$origName,
                'fecha' => now(),
            ]);

            return redirect()->route('importaciones.index')->with('status', 'Importación completada.');
        } catch (\Throwable $e) {
            $imp->estado = 'ERROR';
            $imp->save();
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => 'IMPORTAR_ERROR',
                'tabla_afectada' => $validated['tipo'],
                'id_afectado' => (string)$imp->id_importacion,
                'ip_origen' => $request->ip(),
                'descripcion' => substr('Fallo importación: '.$e->getMessage(),0,255),
                'fecha' => now(),
            ]);
            return back()->withErrors(['archivo' => 'Error al importar: '.$e->getMessage()]);
        }
    }

    public function templateXlsx(string $tipo)
    {
        $this->ensureAuthorized();
        $tipo = strtolower($tipo);
        $map = [
            'docentes' => ['nombre','apellido','correo','telefono','codigo_docente','profesion','grado_academico'],
            'materias' => ['id_carrera','nombre','codigo','carga_horaria','descripcion'],
            'horarios' => ['id_docente_materia_gestion','id_grupo','id_aula','dia','hora_inicio','hora_fin','modalidad','virtual_plataforma','virtual_enlace','observacion'],
        ];
        if (!isset($map[$tipo])) abort(404);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$map[$tipo]], null, 'A1');
        foreach (range('A', chr(ord('A') + count($map[$tipo]) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:'.chr(ord('A') + count($map[$tipo]) - 1).'1')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        $filename = $tipo.'_template.xlsx';
        return response()->streamDownload(function() use ($writer){
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
