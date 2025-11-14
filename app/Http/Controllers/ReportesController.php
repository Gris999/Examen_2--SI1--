<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Aula;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\DocenteMateriaGestion as DMG;

class ReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth_simple','audit']);
    }

    private function canManageReports(): bool
    {
        if (!auth()->check()) return false;
        $roles = auth()->user()->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
        return in_array('administrador',$roles) || in_array('admin',$roles) || in_array('decano',$roles) || in_array('coordinador',$roles);
    }

    private function onlyDocenteScope(): ?int
    {
        if (!auth()->check()) return null;
        $roles = auth()->user()->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
        if (in_array('docente',$roles) && !$this->canManageReports()) {
            $me = Docente::where('id_usuario', auth()->user()->id_usuario ?? 0)->first();
            return $me?->id_docente;
        }
        return null;
    }

    private function logBitacora(string $accion, string $descripcion)
    {
        try {
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => $accion,
                'tabla_afectada' => 'reportes',
                'id_afectado' => null,
                'ip_origen' => request()->ip(),
                'descripcion' => $descripcion,
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {}
    }

    // Dashboard KPIs
    public function dashboard(Request $request)
    {
        $docenteOnly = $this->onlyDocenteScope();

        // KPI: asistencia global
        $qAsis = Asistencia::query();
        if ($docenteOnly) { $qAsis->where('id_docente', $docenteOnly); }
        $total = (clone $qAsis)->count();
        $presentes = (clone $qAsis)->where('estado','PRESENTE')->count();
        $porcAsistencia = $total ? round(($presentes*100.0)/$total, 2) : 0;

        // KPI: docentes con mayor carga (por cantidad de horarios)
        $docentesCarga = Horario::selectRaw('docente_materia_gestion.id_docente as id_docente, COUNT(*) as c')
            ->join('docente_materia_gestion','docente_materia_gestion.id_docente_materia_gestion','=','horarios.id_docente_materia_gestion')
            ->when($docenteOnly, fn($q)=>$q->where('docente_materia_gestion.id_docente',$docenteOnly))
            ->groupBy('docente_materia_gestion.id_docente')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        // KPI: aulas más usadas
        $aulasMasUsadas = Horario::whereNotNull('id_aula')
            ->selectRaw('id_aula, COUNT(*) as usos')
            ->groupBy('id_aula')
            ->orderByDesc('usos')
            ->limit(5)->get();

        // KPI: materias con más ausencias
        $materiasAusencias = Asistencia::selectRaw('gr.id_materia, COUNT(*) as aus')
            ->join('horarios as h','h.id_horario','=','asistencias.id_horario')
            ->join('grupos as gr','gr.id_grupo','=','h.id_grupo')
            ->when($docenteOnly, function($q) { $q->join('docente_materia_gestion as d','d.id_docente_materia_gestion','=','h.id_docente_materia_gestion')->where('d.id_docente', $this->onlyDocenteScope()); })
            ->where('asistencias.estado','AUSENTE')
            ->groupBy('gr.id_materia')
            ->orderByDesc('aus')
            ->limit(5)->get();

        // Series: asistencia por día (últimos 14 días)
        $asisPorDia = Asistencia::selectRaw("fecha, COUNT(*) as c")
            ->when($docenteOnly, fn($q)=>$q->where('id_docente',$docenteOnly))
            ->whereDate('fecha','>=', now()->subDays(14)->toDateString())
            ->groupBy('fecha')->orderBy('fecha')->get();

        // KPI extra: puntualidad por docente (Top 5)
        $puntualidadDocentes = \Illuminate\Support\Facades\DB::table('asistencias as a')
            ->join('docentes as d','d.id_docente','=','a.id_docente')
            ->join('usuarios as u','u.id_usuario','=','d.id_usuario')
            ->when($docenteOnly, fn($q)=>$q->where('a.id_docente',$docenteOnly))
            ->selectRaw("a.id_docente, u.nombre, u.apellido, ROUND(SUM(CASE WHEN a.estado='PRESENTE' THEN 1 ELSE 0 END)*100.0/NULLIF(COUNT(*),0),2) as pct")
            ->groupBy('a.id_docente','u.nombre','u.apellido')
            ->orderByDesc('pct')
            ->limit(5)->get();

        // KPI extra: materias con más ausencias (Top 5)
        $ausentismoMateriasTop = \Illuminate\Support\Facades\DB::table('asistencias as a')
            ->join('horarios as h','h.id_horario','=','a.id_horario')
            ->join('grupos as g','g.id_grupo','=','h.id_grupo')
            ->join('materias as m','m.id_materia','=','g.id_materia')
            ->when($docenteOnly, function($q) { $q->join('docente_materia_gestion as d','d.id_docente_materia_gestion','=','h.id_docente_materia_gestion')->where('d.id_docente', $this->onlyDocenteScope()); })
            ->where('a.estado','AUSENTE')
            ->selectRaw('m.id_materia, m.nombre, COUNT(*) as aus')
            ->groupBy('m.id_materia','m.nombre')
            ->orderByDesc('aus')
            ->limit(5)->get();

        // KPI extra: bloques (hora_inicio) más demandados (Top 6)
        $bloquesDemandados = Horario::selectRaw("to_char(hora_inicio, 'HH24:MI') as bloque, COUNT(*) as c")
            ->groupBy('bloque')
            ->orderByDesc('c')
            ->limit(6)->get();

        // Nombres se resuelven en la vista desde relaciones para evitar duplicaciones.

        $this->logBitacora('REPORT_KPIS','Acceso a dashboard de reportes');

        return view('reportes.dashboard', [
            'porcAsistencia' => $porcAsistencia,
            'docentesCarga' => $docentesCarga,
            'aulasMasUsadas' => $aulasMasUsadas,
            'materiasAusencias' => $materiasAusencias,
            'asisPorDia' => $asisPorDia,
            'puntualidadDocentes' => $puntualidadDocentes,
            'ausentismoMateriasTop' => $ausentismoMateriasTop,
            'bloquesDemandados' => $bloquesDemandados,
            'docenteOnly' => $docenteOnly,
        ]);
    }

    // Reporte de asistencia con filtros y exportes
    public function reporteAsistencia(Request $request)
    {
        $docenteOnly = $this->onlyDocenteScope();
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $docenteOnly ?? $request->integer('docente_id');
        $materiaId = $request->integer('materia_id');
        $gestionId = $request->integer('gestion_id');
        $estado = $request->get('estado');

        $q = Asistencia::with(['docente.usuario','horario.grupo.materia','horario.grupo.gestion','horario.aula'])
            ->when($desde, fn($x)=>$x->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($x)=>$x->where('id_docente', $docenteId))
            ->when($estado, fn($x)=>$x->where('estado', $estado))
            ->when($materiaId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->orderBy('fecha','desc')->orderBy('hora_entrada','desc');
        $asistencias = $q->paginate(20)->withQueryString();

        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();

        $this->logBitacora('REPORT_ASISTENCIA','Consulta de reporte de asistencia');

        return view('reportes.asistencia', compact('asistencias','docentes','materias','gestiones','desde','hasta','docenteId','materiaId','gestionId','estado','docenteOnly'));
    }

    public function exportarAsistenciaExcel(Request $request)
    {
        $docenteOnly = $this->onlyDocenteScope();
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $docenteOnly ?? $request->integer('docente_id');
        $materiaId = $request->integer('materia_id');
        $gestionId = $request->integer('gestion_id');
        $estado = $request->get('estado');

        $rows = Asistencia::with(['docente.usuario','horario.grupo.materia','horario.grupo.gestion','horario.aula'])
            ->when($desde, fn($x)=>$x->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($x)=>$x->where('id_docente', $docenteId))
            ->when($estado, fn($x)=>$x->where('estado', $estado))
            ->when($materiaId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->orderBy('fecha','desc')->orderBy('hora_entrada','desc')
            ->get();

        $docenteNom = $docenteId ? (optional(optional(Docente::with('usuario')->find($docenteId))->usuario)->nombre.' '.optional(optional(Docente::find($docenteId))->usuario)->apellido) : 'Todos';
        $materiaNom = $materiaId ? (Materia::find($materiaId)->nombre ?? '—') : 'Todas';
        $gestionCod = $gestionId ? (Gestion::find($gestionId)->codigo ?? '—') : 'Todas';

        $totales = ['PRESENTE'=>0,'AUSENTE'=>0,'RETRASO'=>0,'JUSTIFICADO'=>0];
        foreach ($rows as $r) { if (isset($totales[$r->estado])) { $totales[$r->estado]++; } }
        $totalRegs = count($rows);

        $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
            .'<title>Reporte de Asistencia</title>'
            .'<style>body{font-family:Segoe UI,Arial,sans-serif;font-size:12px}h3{margin:0 0 6px 0}.small{color:#555;margin:0 0 12px 0}table{border-collapse:collapse;width:100%}th,td{border:1px solid #999;padding:6px}th{background:#f1f5f9;font-weight:700}tbody tr:nth-child(even){background:#fafafa}.nowrap{white-space:nowrap}.badge{padding:2px 6px;border-radius:4px;font-size:11px}.bg-success{background:#16a34a;color:#fff}.bg-danger{background:#dc2626;color:#fff}.bg-warning{background:#f59e0b;color:#000}.bg-info{background:#06b6d4;color:#fff}</style></head><body>'
            .'<h3>Reporte de Asistencia</h3>'
            .'<p class="small">Total: '.e((string)$totalRegs).' · Docente: '.e(trim($docenteNom)).' · Materia: '.e($materiaNom).' · Gestión: '.e($gestionCod).' · Fechas: '.e($desde ?? '—').' a '.e($hasta ?? '—').'</p>'
            .'<table><thead><tr><th>Fecha</th><th>Docente</th><th>Materia</th><th>Grupo</th><th>Gestión</th><th>Aula</th><th>Bloque</th><th>Entrada</th><th>Estado</th><th>Método</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $doc = optional($r->docente->usuario ?? null);
            $hi = optional($r->horario ?? null)->hora_inicio; $hf = optional($r->horario ?? null)->hora_fin; $bloque = ($hi && $hf) ? (substr($hi,0,5).' - '.substr($hf,0,5)) : '';
            $estado = $r->estado; $cls = $estado==='PRESENTE'?'bg-success':($estado==='AUSENTE'?'bg-danger':($estado==='RETRASO'?'bg-warning':'bg-info'));
            $html .= '<tr>'
                .'<td class="nowrap">'.e($r->fecha).'</td>'
                .'<td>'.e(trim(($doc->nombre ?? '').' '.($doc->apellido ?? ''))).'</td>'
                .'<td>'.e(optional($r->horario->grupo->materia ?? null)->nombre).'</td>'
                .'<td>'.e($r->horario->grupo->nombre_grupo ?? '').'</td>'
                .'<td>'.e(optional($r->horario->grupo->gestion ?? null)->codigo).'</td>'
                .'<td>'.e(optional($r->horario->aula ?? null)->nombre).'</td>'
                .'<td class="nowrap">'.e($bloque).'</td>'
                .'<td class="nowrap">'.e($r->hora_entrada ?? '').'</td>'
                .'<td><span class="badge '.$cls.'">'.e($estado).'</span></td>'
                .'<td>'.e($r->metodo).'</td>'
                .'</tr>';
        }
        $html .= '</tbody></table>'
            .'<br><table><thead><tr><th colspan="5">Resumen</th></tr></thead><tbody>'
            .'<tr><td>Total</td><td colspan="4">'.e((string)$totalRegs).'</td></tr>'
            .'<tr><td>Presentes</td><td>'.e((string)$totales['PRESENTE']).'</td><td>Ausentes</td><td>'.e((string)$totales['AUSENTE']).'</td><td></td></tr>'
            .'<tr><td>Retrasos</td><td>'.e((string)$totales['RETRASO']).'</td><td>Justificados</td><td>'.e((string)$totales['JUSTIFICADO']).'</td><td></td></tr>'
            .'</tbody></table></body></html>';

        $this->logBitacora('REPORT_ASISTENCIA','Exportación XLS de asistencia');

        return response("\xEF\xBB\xBF".$html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_asistencia.xls"',
        ]);
    }

    public function exportarAsistenciaPDF(Request $request)
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(501, 'Dompdf no instalado en el servidor');
        }
        // Reutiliza la vista de impresión del historial pero con título distinto
        $request2 = new Request($request->all());
        $controller = app(\App\Http\Controllers\HistorialAsistenciaController::class);
        // Usa el mismo ensamblado de filas que print()
        $rowsView = $controller->print($request2);
        $html = $rowsView->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();

        $this->logBitacora('REPORT_ASISTENCIA','Exportación PDF de asistencia');

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_asistencia.pdf"'
        ]);
    }

    // Reporte de horarios
    public function reporteHorarios(Request $request)
    {
        $docenteOnly = $this->onlyDocenteScope();
        $docenteId = $docenteOnly ?? $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');
        $grupoId = $request->integer('grupo_id');
        $aulaId = $request->integer('aula_id');
        $dia = $request->get('dia');

        $q = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->when($docenteId, function($x) use ($docenteId){
                $ids = DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion');
                $x->whereIn('id_docente_materia_gestion', $ids);
            })
            ->when($gestionId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_gestion',$gestionId)))
            ->when($materiaId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_materia',$materiaId)))
            ->when($grupoId, fn($x)=>$x->where('id_grupo',$grupoId))
            ->when($aulaId, fn($x)=>$x->where('id_aula',$aulaId))
            ->when($dia, fn($x)=>$x->where('dia',$dia))
            ->orderBy('dia')->orderBy('hora_inicio');
        $horarios = $q->paginate(20)->withQueryString();

        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('id_grupo','desc')->get();
        $aulas = Aula::orderBy('nombre')->get();
        $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

        $this->logBitacora('REPORT_HORARIOS','Consulta de reporte de horarios');

        return view('reportes.horarios', compact('horarios','docentes','gestiones','materias','grupos','aulas','dias','docenteId','gestionId','materiaId','grupoId','aulaId','dia'));
    }

    public function exportarHorariosPDF(Request $request)
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(501, 'Dompdf no instalado en el servidor');
        }
        // Reutiliza consulta de reporteHorarios pero sin paginar
        $docenteOnly = $this->onlyDocenteScope();
        $docenteId = $docenteOnly ?? $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');
        $grupoId = $request->integer('grupo_id');
        $aulaId = $request->integer('aula_id');
        $dia = $request->get('dia');

        $rows = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->when($docenteId, function($x) use ($docenteId){
                $ids = DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion');
                $x->whereIn('id_docente_materia_gestion', $ids);
            })
            ->when($gestionId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_gestion',$gestionId)))
            ->when($materiaId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_materia',$materiaId)))
            ->when($grupoId, fn($x)=>$x->where('id_grupo',$grupoId))
            ->when($aulaId, fn($x)=>$x->where('id_aula',$aulaId))
            ->when($dia, fn($x)=>$x->where('dia',$dia))
            ->orderBy('dia')->orderBy('hora_inicio')->get();

        $html = view('reportes.horarios_print', compact('rows','docenteId','gestionId','materiaId','grupoId','aulaId','dia'))->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();

        $this->logBitacora('REPORT_HORARIOS','Exportación PDF de horarios');

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_horarios.pdf"'
        ]);
    }

    public function exportarHorariosExcel(Request $request)
    {
        $docenteOnly = $this->onlyDocenteScope();
        $docenteId = $docenteOnly ?? $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');
        $grupoId = $request->integer('grupo_id');
        $aulaId = $request->integer('aula_id');
        $dia = $request->get('dia');

        $rows = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->when($docenteId, function($x) use ($docenteId){
                $ids = DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion');
                $x->whereIn('id_docente_materia_gestion', $ids);
            })
            ->when($gestionId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_gestion',$gestionId)))
            ->when($materiaId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_materia',$materiaId)))
            ->when($grupoId, fn($x)=>$x->where('id_grupo',$grupoId))
            ->when($aulaId, fn($x)=>$x->where('id_aula',$aulaId))
            ->when($dia, fn($x)=>$x->where('dia',$dia))
            ->orderBy('dia')->orderBy('hora_inicio')->get();

        $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
            .'<title>Reporte de Horarios</title>'
            .'<style>body{font-family:Segoe UI,Arial,sans-serif;font-size:12px}h3{margin:0 0 6px 0}.small{color:#555;margin:0 0 12px 0}table{border-collapse:collapse;width:100%}th,td{border:1px solid #999;padding:6px}th{background:#f1f5f9;font-weight:700}tbody tr:nth-child(even){background:#fafafa}.nowrap{white-space:nowrap}</style></head><body>'
            .'<h3>Reporte de Horarios</h3>'
            .'<table><thead><tr><th>Docente</th><th>Materia</th><th>Grupo</th><th>Gestión</th><th>Día</th><th>Bloque</th><th>Aula</th><th>Modalidad</th></tr></thead><tbody>';
        foreach ($rows as $h) {
            $hi = $h->hora_inicio ?? ''; $hf = $h->hora_fin ?? '';
            $html .= '<tr>'
                .'<td>'.e(optional($h->docenteMateriaGestion->docente->usuario ?? null)->nombre.' '.optional($h->docenteMateriaGestion->docente->usuario ?? null)->apellido).'</td>'
                .'<td>'.e(optional($h->grupo->materia ?? null)->nombre).'</td>'
                .'<td>'.e($h->grupo->nombre_grupo ?? '').'</td>'
                .'<td>'.e(optional($h->grupo->gestion ?? null)->codigo).'</td>'
                .'<td>'.e($h->dia).'</td>'
                .'<td class="nowrap">'.e(($hi && $hf) ? (substr($hi,0,5).' - '.substr($hf,0,5)) : '').'</td>'
                .'<td>'.e(optional($h->aula ?? null)->nombre ?? '-').'</td>'
                .'<td>'.e($h->modalidad ?? '').'</td>'
                .'</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $this->logBitacora('REPORT_HORARIOS','Exportación XLS de horarios');

        return response("\xEF\xBB\xBF".$html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_horarios.xls"',
        ]);
    }

    public function exportarHorariosCsv(Request $request)
    {
        $docenteOnly = $this->onlyDocenteScope();
        $docenteId = $docenteOnly ?? $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');
        $grupoId = $request->integer('grupo_id');
        $aulaId = $request->integer('aula_id');
        $dia = $request->get('dia');

        $rows = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->when($docenteId, function($x) use ($docenteId){
                $ids = DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion');
                $x->whereIn('id_docente_materia_gestion', $ids);
            })
            ->when($gestionId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_gestion',$gestionId)))
            ->when($materiaId, fn($x)=>$x->whereHas('grupo', fn($g)=>$g->where('id_materia',$materiaId)))
            ->when($grupoId, fn($x)=>$x->where('id_grupo',$grupoId))
            ->when($aulaId, fn($x)=>$x->where('id_aula',$aulaId))
            ->when($dia, fn($x)=>$x->where('dia',$dia))
            ->orderBy('dia')->orderBy('hora_inicio')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_horarios.csv"',
        ];

        return response()->stream(function() use ($rows){
            echo "\xEF\xBB\xBF"; echo "sep=,\n";
            $out = fopen('php://output','w');
            fputcsv($out, ['Docente','Materia','Grupo','Gestión','Día','Bloque','Aula','Modalidad']);
            foreach ($rows as $h) {
                $hi = $h->hora_inicio ?? ''; $hf = $h->hora_fin ?? '';
                $bloque = ($hi && $hf) ? (substr($hi,0,5).' - '.substr($hf,0,5)) : '';
                $doc = optional($h->docenteMateriaGestion->docente->usuario ?? null);
                fputcsv($out,[
                    trim(($doc->nombre ?? '').' '.($doc->apellido ?? '')),
                    optional($h->grupo->materia ?? null)->nombre,
                    $h->grupo->nombre_grupo ?? '',
                    optional($h->grupo->gestion ?? null)->codigo,
                    $h->dia,
                    $bloque,
                    optional($h->aula ?? null)->nombre,
                    $h->modalidad ?? '',
                ]);
            }
            fclose($out);
        },200,$headers);
    }

    // Reporte de Aulas (ocupación y disponibilidad)
    public function reporteAulas(Request $request)
    {
        $dia = $request->get('dia');
        $hora_inicio = $request->get('hora_inicio');
        $hora_fin = $request->get('hora_fin');

        // Aulas más usadas
        $masUsadas = Horario::whereNotNull('id_aula')
            ->selectRaw('id_aula, COUNT(*) as usos')
            ->groupBy('id_aula')->orderByDesc('usos')->limit(10)->get();

        // Disponibles según filtro
        $disponibles = collect();
        if ($dia && $hora_inicio && $hora_fin) {
            $ocupadas = Horario::select('id_aula')
                ->where('dia',$dia)
                ->whereNotNull('id_aula')
                ->where(function($q) use ($hora_inicio,$hora_fin){
                    $q->where('hora_inicio','<',$hora_fin)->where('hora_fin','>',$hora_inicio);
                })->pluck('id_aula')->unique();
            $disponibles = Aula::whereNotIn('id_aula',$ocupadas)->orderBy('nombre')->get();
        }

        $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        $this->logBitacora('REPORT_AULAS','Consulta de reporte de aulas');

        return view('reportes.aulas', compact('dia','hora_inicio','hora_fin','masUsadas','disponibles','dias'));
    }

    // Alias de compatibilidad con rutas antiguas (si existen)
    public function index(Request $request) { return $this->dashboard($request); }
    public function export(Request $request) { return $this->exportarAsistenciaExcel($request); }
    public function print(Request $request) { return $this->exportarAsistenciaPDF($request); }

    // CU12: Exportes de Aulas
    public function exportarAulasExcel(Request $request)
    {
        $dia = $request->get('dia');
        $hora_inicio = $request->get('hora_inicio');
        $hora_fin = $request->get('hora_fin');

        $masUsadas = Horario::whereNotNull('id_aula')
            ->selectRaw('id_aula, COUNT(*) as usos')
            ->groupBy('id_aula')->orderByDesc('usos')->limit(50)->get();

        $disponibles = collect();
        if ($dia && $hora_inicio && $hora_fin) {
            $ocupadas = Horario::select('id_aula')
                ->where('dia',$dia)
                ->whereNotNull('id_aula')
                ->where(function($q) use ($hora_inicio,$hora_fin){
                    $q->where('hora_inicio','<',$hora_fin)->where('hora_fin','>',$hora_inicio);
                })->pluck('id_aula')->unique();
            $disponibles = Aula::whereNotIn('id_aula',$ocupadas)->orderBy('nombre')->get();
        }

        $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
            .'<title>Reporte de Aulas</title>'
            .'<style>body{font-family:Segoe UI,Arial,sans-serif;font-size:12px}h3{margin:0 0 6px 0}.small{color:#555;margin:0 0 12px 0}table{border-collapse:collapse;width:100%}th,td{border:1px solid #999;padding:6px}th{background:#f1f5f9;font-weight:700}tbody tr:nth-child(even){background:#fafafa}</style></head><body>'
            .'<h3>Reporte de Aulas</h3>'
            .'<p class="small">Día: '.e($dia ?? '—').' · Rango: '.e($hora_inicio ?? '—').' a '.e($hora_fin ?? '—').'</p>';

        // Tabla de disponibles
        $html .= '<h4 style="margin:8px 0 4px 0">Aulas disponibles</h4>';
        $html .= '<table><thead><tr><th>Aula</th><th>Código</th><th>Capacidad</th><th>Ubicación</th></tr></thead><tbody>';
        foreach ($disponibles as $a) {
            $html .= '<tr>'
                .'<td>'.e($a->nombre).'</td>'
                .'<td>'.e($a->codigo ?? '').'</td>'
                .'<td>'.e((string)($a->capacidad ?? '')).'</td>'
                .'<td>'.e($a->ubicacion ?? '').'</td>'
                .'</tr>';
        }
        if ($disponibles->isEmpty()) { $html .= '<tr><td colspan="4">Sin resultados (aplica filtros)</td></tr>'; }
        $html .= '</tbody></table>';

        // Tabla de más usadas
        $html .= '<h4 style="margin:16px 0 4px 0">Aulas más usadas</h4>';
        $html .= '<table><thead><tr><th>Aula</th><th>Código</th><th>Usos</th></tr></thead><tbody>';
        foreach ($masUsadas as $u) {
            $au = Aula::find($u->id_aula);
            $html .= '<tr>'
                .'<td>'.e($au->nombre ?? '—').'</td>'
                .'<td>'.e($au->codigo ?? '').'</td>'
                .'<td>'.e((string)$u->usos).'</td>'
                .'</tr>';
        }
        if ($masUsadas->isEmpty()) { $html .= '<tr><td colspan="3">Sin datos</td></tr>'; }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        $this->logBitacora('REPORT_AULAS','Exportación XLS de aulas');

        return response("\xEF\xBB\xBF".$html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_aulas.xls"',
        ]);
    }

    public function exportarAulasCsv(Request $request)
    {
        $dia = $request->get('dia');
        $hora_inicio = $request->get('hora_inicio');
        $hora_fin = $request->get('hora_fin');

        $masUsadas = Horario::whereNotNull('id_aula')
            ->selectRaw('id_aula, COUNT(*) as usos')
            ->groupBy('id_aula')->orderByDesc('usos')->limit(50)->get();

        $disponibles = collect();
        if ($dia && $hora_inicio && $hora_fin) {
            $ocupadas = Horario::select('id_aula')
                ->where('dia',$dia)
                ->whereNotNull('id_aula')
                ->where(function($q) use ($hora_inicio,$hora_fin){
                    $q->where('hora_inicio','<',$hora_fin)->where('hora_fin','>',$hora_inicio);
                })->pluck('id_aula')->unique();
            $disponibles = Aula::whereNotIn('id_aula',$ocupadas)->orderBy('nombre')->get();
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_aulas.csv"',
        ];

        return response()->stream(function() use ($disponibles,$masUsadas){
            echo "\xEF\xBB\xBF"; echo "sep=,\n";
            $out = fopen('php://output','w');
            // Sección: Disponibles
            fputcsv($out, ['SECCION','AULAS DISPONIBLES']);
            fputcsv($out, ['Aula','Código','Capacidad','Ubicación']);
            if ($disponibles->isEmpty()) {
                fputcsv($out, ['Sin resultados (aplica filtros)']);
            } else {
                foreach ($disponibles as $a) {
                    fputcsv($out, [
                        $a->nombre,
                        $a->codigo,
                        $a->capacidad,
                        $a->ubicacion,
                    ]);
                }
            }
            // Separador
            fputcsv($out, []);
            // Sección: Más usadas
            fputcsv($out, ['SECCION','AULAS MAS USADAS']);
            fputcsv($out, ['Aula','Código','Usos']);
            if ($masUsadas->isEmpty()) {
                fputcsv($out, ['Sin datos']);
            } else {
                foreach ($masUsadas as $u) {
                    $au = Aula::find($u->id_aula);
                    fputcsv($out, [
                        $au->nombre ?? '—',
                        $au->codigo ?? '',
                        $u->usos,
                    ]);
                }
            }
            fclose($out);
        },200,$headers);
    }

    public function exportarAulasPDF(Request $request)
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(501, 'Dompdf no instalado en el servidor');
        }

        $dia = $request->get('dia');
        $hora_inicio = $request->get('hora_inicio');
        $hora_fin = $request->get('hora_fin');

        $masUsadas = Horario::whereNotNull('id_aula')
            ->selectRaw('id_aula, COUNT(*) as usos')
            ->groupBy('id_aula')->orderByDesc('usos')->limit(50)->get();

        $disponibles = collect();
        if ($dia && $hora_inicio && $hora_fin) {
            $ocupadas = Horario::select('id_aula')
                ->where('dia',$dia)
                ->whereNotNull('id_aula')
                ->where(function($q) use ($hora_inicio,$hora_fin){
                    $q->where('hora_inicio','<',$hora_fin)->where('hora_fin','>',$hora_inicio);
                })->pluck('id_aula')->unique();
            $disponibles = Aula::whereNotIn('id_aula',$ocupadas)->orderBy('nombre')->get();
        }

        $html = view('reportes.aulas_print', compact('dia','hora_inicio','hora_fin','masUsadas','disponibles'))->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();

        $this->logBitacora('REPORT_AULAS','Exportación PDF de aulas');

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_aulas.pdf"'
        ]);
    }
}
