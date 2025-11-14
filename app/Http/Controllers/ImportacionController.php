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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportacionController extends Controller
{
    private function expectedMap(): array
    {
        return [
            'docentes' => ['nombre','apellido','correo','telefono','codigo_docente','profesion','grado_academico'],
            'materias' => ['id_carrera','nombre','codigo','carga_horaria','descripcion'],
            'horarios' => ['id_docente_materia_gestion','id_grupo','id_aula','dia','hora_inicio','hora_fin','modalidad','virtual_plataforma','virtual_enlace','observacion'],
        ];
    }

    private function firstRowKeys(\Illuminate\Support\Collection $rows): array
    {
        $first = $rows->first();
        if (!is_array($first)) {
            $first = $rows->first(function($r){ return is_array($r); });
        }
        if (!$first) return [];
        return array_map(fn($k)=>mb_strtolower((string)$k), array_keys($first));
    }

    private function validateHeadersFor(string $type, \Illuminate\Support\Collection $sheet): array
    {
        $map = $this->expectedMap();
        $expected = $map[$type] ?? [];
        $got = $this->firstRowKeys($sheet);
        $missing = [];
        foreach ($expected as $col) { if (!in_array(mb_strtolower($col), $got, true)) { $missing[] = $col; } }
        return $missing;
    }
    private function detectSheetType(\Illuminate\Support\Collection $rows): ?string
    {
        $first = $rows->first();
        if (!is_array($first) && !($first instanceof \ArrayAccess)) {
            $first = $rows->first(function($r){ return is_array($r) || $r instanceof \ArrayAccess; });
            if (!$first) return null;
        }
        $keys = array_map(fn($k)=>mb_strtolower((string)$k), array_keys((array)$first));
        $has = fn($cols)=>count(array_intersect($cols, $keys))===count($cols);
        if ($has(['correo','nombre','apellido'])) return 'docentes';
        if ($has(['id_carrera','codigo','nombre'])) return 'materias';
        if ($has(['id_docente_materia_gestion','id_grupo','dia','hora_inicio','hora_fin'])) return 'horarios';
        return null;
    }
    private function ensureCanView(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles) || in_array('decano',$roles) || in_array('coordinador',$roles);
        abort_unless($ok, 403);
    }

    private function ensureCanImport(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('coordinador',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles);
        abort_unless($ok, 403);
    }

    public function index()
    {
        $this->ensureCanView();
        $imports = ImportacionUsuario::orderBy('fecha','desc')->orderBy('id_importacion','desc')->paginate(20);
        return view('importaciones.index', compact('imports'));
    }

    public function create()
    {
        $this->ensureCanImport();
        $supportsExcel = class_exists(\Maatwebsite\Excel\Excel::class);
        return view('importaciones.create', compact('supportsExcel'));
    }

    public function store(Request $request)
    {
        $this->ensureCanImport();
        $validated = $request->validate([
            'tipo' => 'required|in:docentes,materias,horarios,todo',
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
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
        $total = 0; $sheets = collect();
        try {
            $sheets = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow {
                public function collection(\Illuminate\Support\Collection $rows) {}
            }, $file);
            if ($validated['tipo'] === 'todo') {
                foreach ($sheets as $sheet) {
                    $type = $this->detectSheetType($sheet);
                    if (in_array($type, ['docentes','materias','horarios'])) {
                        $total += $sheet->count();
                    }
                }
            } else {
                if ($sheets->count() > 0) { $total = $sheets[0]->count(); }
            }
        } catch (\Throwable $e) {
            // continuar con total 0
        }
        $imp->total_filas = $total;
        $imp->save();

        // Validación de encabezados antes de importar
        $errors = 0;
        try {
            $issues = [];
            $map = $this->expectedMap();
            if ($validated['tipo'] === 'todo') {
                foreach ($sheets as $sheet) {
                    $type = $this->detectSheetType($sheet);
                    if (in_array($type, ['docentes','materias','horarios'])) {
                        $missing = $this->validateHeadersFor($type, $sheet);
                        if (count($missing)) { $issues[] = ucfirst($type).': faltan columnas ['.implode(', ', $missing).']'; }
                    }
                }
            } else {
                if ($sheets->count() > 0) {
                    $missing = $this->validateHeadersFor($validated['tipo'], $sheets[0]);
                    if (count($missing)) { $issues[] = ucfirst($validated['tipo']).': faltan columnas ['.implode(', ', $missing).']'; }
                }
            }
            if (count($issues)) {
                $imp->estado = 'ERROR';
                $imp->save();
                DB::table('bitacora')->insert([
                    'id_usuario' => auth()->user()->id_usuario ?? null,
                    'accion' => 'IMPORTAR_ERROR',
                    'tabla_afectada' => 'varias',
                    'id_afectado' => (string)$imp->id_importacion,
                    'ip_origen' => $request->ip(),
                    'descripcion' => substr('Encabezados faltantes: '.implode(' | ', $issues), 0, 255),
                    'fecha' => now(),
                ]);
                return back()->withErrors(['archivo' => "Encabezados faltantes:\n - ".implode("\n - ", $issues)])->withInput();
            }

            // Ejecutar import correspondiente
            $tipo = $validated['tipo'];
            if ($tipo === 'docentes') {
                Excel::import(new DocentesImport($imp->id_importacion, auth()->user()->id_usuario ?? 0), $file);
                $tabla = 'docentes';
            } elseif ($tipo === 'materias') {
                Excel::import(new MateriasImport($imp->id_importacion, auth()->user()->id_usuario ?? 0), $file);
                $tabla = 'materias';
            } elseif ($tipo === 'horarios') {
                Excel::import(new HorariosImport($imp->id_importacion, auth()->user()->id_usuario ?? 0), $file);
                $tabla = 'horarios';
            } else {
                // Importación total: procesa por cada hoja detectada
                $tabla = 'varias';
                if ($sheets instanceof \Illuminate\Support\Collection) {
                    foreach ($sheets as $sheet) {
                        $type = $this->detectSheetType($sheet);
                        if ($type === 'docentes') {
                            (new DocentesImport($imp->id_importacion, auth()->user()->id_usuario ?? 0))->collection($sheet);
                        } elseif ($type === 'materias') {
                            (new MateriasImport($imp->id_importacion, auth()->user()->id_usuario ?? 0))->collection($sheet);
                        } elseif ($type === 'horarios') {
                            (new HorariosImport($imp->id_importacion, auth()->user()->id_usuario ?? 0))->collection($sheet);
                        }
                    }
                }
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
        $this->ensureCanImport();
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

    public function templateMasterXlsx()
    {
        $this->ensureCanImport();

        $maps = [
            'docentes' => ['nombre','apellido','correo','telefono','codigo_docente','profesion','grado_academico'],
            'materias' => ['id_carrera','nombre','codigo','carga_horaria','descripcion'],
            'horarios' => ['id_docente_materia_gestion','id_grupo','id_aula','dia','hora_inicio','hora_fin','modalidad','virtual_plataforma','virtual_enlace','observacion'],
        ];

        $spreadsheet = new Spreadsheet();
        // Sheet 1: docentes
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('docentes');
        $sheet1->fromArray([$maps['docentes']], null, 'A1');
        foreach (range('A', chr(ord('A') + count($maps['docentes']) - 1)) as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->getStyle('A1:'.chr(ord('A') + count($maps['docentes']) - 1).'1')->getFont()->setBold(true);

        // Sheet 2: materias
        $sheet2 = new Worksheet($spreadsheet, 'materias');
        $spreadsheet->addSheet($sheet2, 1);
        $sheet2->fromArray([$maps['materias']], null, 'A1');
        foreach (range('A', chr(ord('A') + count($maps['materias']) - 1)) as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet2->getStyle('A1:'.chr(ord('A') + count($maps['materias']) - 1).'1')->getFont()->setBold(true);

        // Sheet 3: horarios
        $sheet3 = new Worksheet($spreadsheet, 'horarios');
        $spreadsheet->addSheet($sheet3, 2);
        $sheet3->fromArray([$maps['horarios']], null, 'A1');
        foreach (range('A', chr(ord('A') + count($maps['horarios']) - 1)) as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet3->getStyle('A1:'.chr(ord('A') + count($maps['horarios']) - 1).'1')->getFont()->setBold(true);

        // Make first sheet active again
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'import_total_template.xlsx';
        return response()->streamDownload(function() use ($writer){
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
