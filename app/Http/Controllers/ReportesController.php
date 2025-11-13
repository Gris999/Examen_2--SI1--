<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Materia;
use App\Models\Horario;
use App\Models\Aula;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    private function ensureAuthorized(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles);
        abort_unless($ok, 403);
    }

    public function index(Request $request)
    {
        $this->ensureAuthorized();

        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');

        $base = Asistencia::when($desde, fn($q)=>$q->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($q)=>$q->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($q)=>$q->where('id_docente',$docenteId))
            ->when($materiaId, fn($q)=>$q->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($q)=>$q->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)));

        $total = (clone $base)->count();
        $porEstado = (clone $base)->selectRaw('estado, COUNT(*) c')->groupBy('estado')->pluck('c','estado');
        $presentes = (int)($porEstado['PRESENTE'] ?? 0);
        $retrasos = (int)($porEstado['RETRASO'] ?? 0);
        $ausentes = (int)($porEstado['AUSENTE'] ?? 0);
        $justificados = (int)($porEstado['JUSTIFICADO'] ?? 0);
        $asistenciaPct = $total > 0 ? round($presentes * 100 / $total, 1) : 0.0;

        // Top 5 retrasos por docente
        $topRetrasos = (clone $base)
            ->where('estado','RETRASO')
            ->selectRaw('id_docente, COUNT(*) c')
            ->groupBy('id_docente')
            ->orderByDesc('c')
            ->with('docente.usuario')
            ->limit(5)
            ->get();

        // Horarios activos y aulas disponibles (aproximado global o filtrado por gestión)
        $horariosActivos = Horario::when($gestionId, function($q) use ($gestionId){
                $q->whereHas('grupo', fn($h)=>$h->where('id_gestion',$gestionId));
            })->count();

        $totalAulas = Aula::count();
        $aulasUsadas = Horario::when($gestionId, fn($q)=>$q->whereHas('grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->whereNotNull('id_aula')
            ->distinct('id_aula')
            ->count('id_aula');
        $aulasDisponibles = max(0, $totalAulas - $aulasUsadas);

        // Colecciones para filtros
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();
        $materias = Materia::orderBy('nombre')->get();

        // Stacked dataset: asistencia por gestión (labels = código de gestión; datasets por estado)
        $agru = Asistencia::join('horarios as h','h.id_horario','=','asistencias.id_horario')
            ->join('grupos as g','g.id_grupo','=','h.id_grupo')
            ->join('gestiones as ge','ge.id_gestion','=','g.id_gestion')
            ->when($desde, fn($q)=>$q->whereDate('asistencias.fecha','>=',$desde))
            ->when($hasta, fn($q)=>$q->whereDate('asistencias.fecha','<=',$hasta))
            ->when($docenteId, fn($q)=>$q->where('asistencias.id_docente',$docenteId))
            ->when($materiaId, fn($q)=>$q->where('g.id_materia',$materiaId))
            ->when($gestionId, fn($q)=>$q->where('g.id_gestion',$gestionId))
            ->selectRaw('ge.codigo as gestion, asistencias.estado, COUNT(*) c')
            ->groupBy('ge.codigo','asistencias.estado')
            ->orderBy('ge.codigo')
            ->get();

        $estados = ['PRESENTE','RETRASO','AUSENTE','JUSTIFICADO'];
        $gestionLabels = $agru->pluck('gestion')->unique()->values()->all();
        $colorMap = [
            'PRESENTE'   => '#22c55e',
            'RETRASO'    => '#f59e0b',
            'AUSENTE'    => '#ef4444',
            'JUSTIFICADO'=> '#06b6d4',
        ];
        $gestionDatasets = [];
        foreach ($estados as $e) {
            $data = [];
            foreach ($gestionLabels as $gcod) {
                $val = $agru->first(fn($r)=>$r->gestion===$gcod && $r->estado===$e)?->c ?? 0;
                $data[] = (int)$val;
            }
            $gestionDatasets[] = [
                'label' => ucfirst(strtolower($e)),
                'data' => $data,
                'backgroundColor' => $colorMap[$e] ?? '#999',
                'stack' => 'asistencia',
            ];
        }

        // Serie para gráfico de barras por estado
        $seriesEstados = [
            'labels' => ['Presente','Retraso','Ausente','Justificado'],
            'data' => [ $presentes, $retrasos, $ausentes, $justificados ],
        ];

        return view('reportes.index', compact(
            'desde','hasta','docenteId','gestionId','materiaId',
            'docentes','gestiones','materias',
            'asistenciaPct','presentes','retrasos','ausentes','justificados','total',
            'topRetrasos','horariosActivos','aulasDisponibles','seriesEstados',
            'gestionLabels','gestionDatasets'
        ));
    }

    public function export(Request $request)
    {
        $this->ensureAuthorized();
        // Reutiliza la lógica base
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');

        $rows = Asistencia::with(['docente.usuario','horario.grupo.materia','horario.grupo.gestion','horario.aula'])
            ->when($desde, fn($q)=>$q->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($q)=>$q->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($q)=>$q->where('id_docente',$docenteId))
            ->when($materiaId, fn($q)=>$q->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($q)=>$q->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->orderBy('fecha','desc')->orderBy('hora_entrada','desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reporte_asistencia.csv"',
        ];

        return response()->stream(function() use ($rows){
            $out = fopen('php://output','w');
            fputcsv($out, ['Fecha','Docente','Materia','Grupo','Gestión','Aula','Entrada','Estado','Método']);
            foreach($rows as $r){
                $doc = optional($r->docente->usuario ?? null);
                fputcsv($out,[
                    $r->fecha,
                    trim(($doc->nombre ?? '').' '.($doc->apellido ?? '')),
                    optional($r->horario->grupo->materia ?? null)->nombre,
                    $r->horario->grupo->nombre_grupo ?? '',
                    optional($r->horario->grupo->gestion ?? null)->codigo,
                    optional($r->horario->aula ?? null)->nombre,
                    $r->hora_entrada,
                    $r->estado,
                    $r->metodo,
                ]);
            }
            fclose($out);
        },200,$headers);
    }

    public function print(Request $request)
    {
        $this->ensureAuthorized();
        // Para imprimir, reutilizamos index simplificado: solo pasamos KPIs y lista de asistencia
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');

        $rows = Asistencia::with(['docente.usuario','horario.grupo.materia','horario.grupo.gestion','horario.aula'])
            ->when($desde, fn($q)=>$q->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($q)=>$q->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($q)=>$q->where('id_docente',$docenteId))
            ->when($materiaId, fn($q)=>$q->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($q)=>$q->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->orderBy('fecha','desc')->orderBy('hora_entrada','desc')
            ->get();

        return view('reportes.print', compact('rows','desde','hasta','docenteId','gestionId','materiaId'));
    }
}
