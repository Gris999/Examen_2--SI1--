<?php

namespace App\Http\Controllers;

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

        // Resumen para gráfica (por estado)
        $resumen = Asistencia::when($desde, fn($x)=>$x->whereDate('fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('fecha','<=',$hasta))
            ->when($docenteId, fn($x)=>$x->where('id_docente', $docenteId))
            ->when($materiaId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_materia',$materiaId)))
            ->when($gestionId, fn($x)=>$x->whereHas('horario.grupo', fn($h)=>$h->where('id_gestion',$gestionId)))
            ->selectRaw("estado, COUNT(*) as c")
            ->groupBy('estado')
            ->pluck('c','estado');

        return view('historial.index', compact(
            'asistencias','docentes','materias','gestiones',
            'desde','hasta','docenteId','materiaId','gestionId','estado','resumen','docSolo'
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
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="historial_asistencia.csv"',
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
