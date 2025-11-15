<?php

namespace App\Http\Controllers\Attendance;
use App\Http\Controllers\Controller;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Gestion;
use Illuminate\Http\Request;

class HistorialAsistenciaController extends Controller
{
    private function resolveHistorialScope(Request $request): array
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $isAdmin = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('decano',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles);
        $isDocente = in_array('docente', $roles);

        $docenteId = null; $docSolo = false;
        if ($isAdmin) return [null, false];
        if ($isDocente) {
            $doc = \App\Models\Docente::where('id_usuario', $u->id_usuario)->first();
            if ($doc) { $docenteId = $doc->id_docente; $docSolo = true; }
            return [$docenteId, $docSolo];
        }
        abort(403);
    }

    public function index(Request $request)
    {
        [$forcedDocenteId, $docSolo] = $this->resolveHistorialScope($request);
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $forcedDocenteId ?? $request->integer('docente_id');
        $materiaId = $request->integer('materia_id');
        $gestionId = $request->integer('gestion_id');
        $estado = $request->get('estado');

        $q = Asistencia::with(['docente.usuario','horario.grupo.materia','horario.grupo.gestion','horario.aula'])
            ->when($desde, fn($x)=>$x->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($x)=>$x->where('id_docente', $docenteId))
            ->when($estado, fn($x)=>$x->where('estado', $estado))
            ->when($materiaId, function($x) use ($materiaId){
                $x->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId));
            })
            ->when($gestionId, function($x) use ($gestionId){
                $x->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId));
            })
            ->orderBy('fecha','desc')->orderBy('hora_entrada','desc');

        $asistencias = $q->paginate(20)->withQueryString();

        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();

        // Resumen para gráfica (por estado) — respeta TODOS los filtros
        $resumen = Asistencia::when($desde, fn($x)=>$x->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($x)=>$x->where('id_docente', $docenteId))
            ->when($estado, fn($x)=>$x->where('estado', $estado))
            ->when($materiaId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->selectRaw("estado, COUNT(*) as c")
            ->groupBy('estado')
            ->pluck('c','estado');

        // Normaliza el orden y agrega ceros para estados faltantes
        $ordenEstados = ['PRESENTE','AUSENTE','RETRASO','JUSTIFICADO'];
        $resumenChart = [];
        foreach ($ordenEstados as $e) { $resumenChart[$e] = (int) ($resumen[$e] ?? 0); }

        return view('historial.index', compact(
            'asistencias','docentes','materias','gestiones',
            'desde','hasta','docenteId','materiaId','gestionId','estado','resumen','resumenChart','docSolo'
        ));
    }

    public function exportCsv(Request $request)
    {
        [$forcedDocenteId, $docSolo] = $this->resolveHistorialScope($request);
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $forcedDocenteId ?? $request->integer('docente_id');
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

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="historial_asistencia.csv"',
        ];

        return response()->stream(function() use ($rows){
            // BOM para Excel y acentos
            echo "\xEF\xBB\xBF";
            // Sugerir separador a Excel
            echo "sep=,\n";

            $out = fopen('php://output','w');
            fputcsv($out, ['Fecha','Docente','Materia','Grupo','Gestión','Aula','Bloque','Entrada','Estado','Método']);
            $totales = ['PRESENTE'=>0,'AUSENTE'=>0,'RETRASO'=>0,'JUSTIFICADO'=>0];
            foreach($rows as $r){
                $doc = optional($r->docente->usuario ?? null);
                $estado = $r->estado;
                if (isset($totales[$estado])) { $totales[$estado]++; }
                $hi = optional($r->horario ?? null)->hora_inicio;
                $hf = optional($r->horario ?? null)->hora_fin;
                $bloque = ($hi && $hf) ? (substr($hi,0,5).' - '.substr($hf,0,5)) : '';
                fputcsv($out,[
                    $r->fecha,
                    trim(($doc->nombre ?? '').' '.($doc->apellido ?? '')),
                    optional($r->horario->grupo->materia ?? null)->nombre,
                    $r->horario->grupo->nombre_grupo ?? '',
                    optional($r->horario->grupo->gestion ?? null)->codigo,
                    optional($r->horario->aula ?? null)->nombre,
                    $bloque,
                    $r->hora_entrada,
                    $estado,
                    $r->metodo,
                ]);
            }
            // Fila en blanco y resumen
            fputcsv($out, []);
            fputcsv($out, ['Resumen','','','','','','']);
            fputcsv($out, ['Presentes',$totales['PRESENTE'],'Ausentes',$totales['AUSENTE'],'Retrasos',$totales['RETRASO'],'Justificados',$totales['JUSTIFICADO']]);
            fclose($out);
        },200,$headers);
    }

    // Exportador "Excel" sin dependencias (HTML table con header XLS)
    public function exportXlsx(Request $request)
    {
        [$forcedDocenteId, $docSolo] = $this->resolveHistorialScope($request);
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $forcedDocenteId ?? $request->integer('docente_id');
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

        // Nombres de filtros para cabecera
        $docenteNom = $docenteId ? (optional(optional(\App\Models\Docente::with('usuario')->find($docenteId))->usuario)->nombre.' '.optional(optional(\App\Models\Docente::find($docenteId))->usuario)->apellido) : 'Todos';
        $materiaNom = $materiaId ? (\App\Models\Materia::find($materiaId)->nombre ?? '—') : 'Todas';
        $gestionCod = $gestionId ? (\App\Models\Gestion::find($gestionId)->codigo ?? '—') : 'Todas';

        // Totales por estado
        $totales = ['PRESENTE'=>0,'AUSENTE'=>0,'RETRASO'=>0,'JUSTIFICADO'=>0];
        foreach ($rows as $r) { if (isset($totales[$r->estado])) { $totales[$r->estado]++; } }
        $totalRegs = count($rows);
        // Texto de filtros
        $filters = [];
        if ($docenteId) { $filters[] = 'Docente: '.e(trim($docenteNom)); }
        if ($materiaId) { $filters[] = 'Materia: '.e($materiaNom); }
        if ($gestionId) { $filters[] = 'Gestión: '.e($gestionCod); }
        if (!empty($estado)) { $filters[] = 'Estado: '.e($estado); }
        if (!empty($desde) || !empty($hasta)) { $filters[] = 'Fechas: '.e($desde ?? '—').' a '.e($hasta ?? '—'); }
        $filtersTxt = count($filters) ? implode(' · ', $filters) : 'Sin filtros';

        // HTML con estilos inline para que Excel lo abra bonito
        $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
            .'<title>Historial de Asistencia</title>'
            .'<style>
                body{font-family:Segoe UI,Arial,sans-serif;font-size:12px}
                h3{margin:0 0 6px 0}
                .small{color:#555;margin:0 0 12px 0}
                table{border-collapse:collapse;width:100%}
                th,td{border:1px solid #999;padding:6px}
                th{background:#f1f5f9;font-weight:700}
                tbody tr:nth-child(even){background:#fafafa}
                .text-center{text-align:center}
                .nowrap{white-space:nowrap}
                .badge{padding:2px 6px;border-radius:4px;font-size:11px}
                .bg-success{background:#16a34a;color:#fff}
                .bg-danger{background:#dc2626;color:#fff}
                .bg-warning{background:#f59e0b;color:#000}
                .bg-info{background:#06b6d4;color:#fff}
              </style></head><body>'
            .'<h3>Historial de Asistencia</h3>'
            .'<p class="small">Total de asistencias: '.e((string)$totalRegs).' · '.$filtersTxt.'</p>'
            .'<table><thead><tr>'
            .'<th>Fecha</th><th>Docente</th><th>Materia</th><th>Grupo</th><th>Gestión</th><th>Aula</th><th>Bloque</th><th>Entrada</th><th>Estado</th><th>Método</th>'
            .'</tr></thead><tbody>';
        foreach ($rows as $r) {
            $doc = optional($r->docente->usuario ?? null);
            $estado = $r->estado;
            $cls = $estado==='PRESENTE'?'bg-success':($estado==='AUSENTE'?'bg-danger':($estado==='RETRASO'?'bg-warning':'bg-info'));
            $hi = optional($r->horario ?? null)->hora_inicio;
            $hf = optional($r->horario ?? null)->hora_fin;
            $bloque = ($hi && $hf) ? (substr($hi,0,5).' - '.substr($hf,0,5)) : '';
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
            .'</tbody></table>'
            .'</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="historial_asistencia.xls"',
        ];
        return response("\xEF\xBB\xBF".$html, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        [$forcedDocenteId, $docSolo] = $this->resolveHistorialScope($request);
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(501, 'Dompdf no instalado en el servidor');
        }
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $forcedDocenteId ?? $request->integer('docente_id');
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

        $html = view('historial.print', compact('rows','desde','hasta','docenteId','materiaId','gestionId','estado'))->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="historial.pdf"'
        ]);
    }

    public function print(Request $request)
    {
        // Reusa la consulta pero sin paginar
        $req = $request->merge(['page'=>null]);
        $request = $req; // superficial
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $request->integer('docente_id');
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

        return view('historial.print', compact('rows','desde','hasta','docenteId','materiaId','gestionId','estado'));
    }
}
