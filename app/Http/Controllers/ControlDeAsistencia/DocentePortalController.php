<?php

namespace App\Http\Controllers\ControlDeAsistencia;
use App\Http\Controllers\Controller;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Horario;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocentePortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:docente');
    }

    public function index(Request $request)
    {
        $docente = $this->docenteActual();
        if (!$docente) {
            abort(404);
        }

        $horarios = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->whereHas('docenteMateriaGestion', fn($q)=> $q->where('id_docente', $docente->id_docente))
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $asistencias = Asistencia::with('horario.grupo')
            ->where('id_docente', $docente->id_docente)
            ->orderBy('fecha','desc')
            ->limit(5)
            ->get();

        $total = Asistencia::where('id_docente', $docente->id_docente)->count();
        $presentes = Asistencia::where('id_docente', $docente->id_docente)->where('estado','PRESENTE')->count();
        $retrasos = Asistencia::where('id_docente', $docente->id_docente)->where('estado','RETRASO')->count();
        $ausentes = Asistencia::where('id_docente', $docente->id_docente)->where('estado','AUSENTE')->count();
        $justificadas = Asistencia::where('id_docente', $docente->id_docente)->where('estado','JUSTIFICADO')->count();

        $presencia = $total ? round(($presentes / $total)*100, 1) : 0;

        $hoy = \Carbon\Carbon::now('America/La_Paz')->format('Y-m-d');
        $horariosHoy = $horarios->where('dia', $this->dowName(\Carbon\Carbon::now('America/La_Paz')->dayOfWeekIso));
        $primerHorarioHoy = $horariosHoy->first();

        return view('docentes.portal', compact(
            'docente','horarios','asistencias','total','presentes','retrasos','ausentes','justificadas','presencia','horariosHoy','hoy','primerHorarioHoy'
        ));
    }

    public function exportHorarios()
    {
        $docente = $this->docenteActual();
        if (!$docente) {
            abort(404);
        }

        $horarios = Horario::with(['grupo.materia','grupo.gestion','aula'])
            ->whereHas('docenteMateriaGestion', fn($q)=> $q->where('id_docente', $docente->id_docente))
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $fecha = \Carbon\Carbon::now('America/La_Paz')->format('Ymd');
        $filename = "horario-semanal-{$fecha}.csv";

        $response = new StreamedResponse(function () use ($horarios) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Día','Materia','Grupo','Aula','Hora'], ';');
            foreach ($horarios as $horario) {
                fputcsv($handle, [
                    $horario->dia,
                    $horario->grupo->materia->nombre ?? '',
                    $horario->grupo->nombre_grupo ?? '',
                    $horario->aula->nombre ?? '-',
                    sprintf('%s - %s',
                        substr($horario->hora_inicio, 0, 5),
                        substr($horario->hora_fin, 0, 5)
                    ),
                ], ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }

    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if (!$user) { return null; }
        return Docente::where('id_usuario', $user->id_usuario ?? 0)->first();
    }

    private function dowName(int $iso): string
    {
        return match($iso) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            default => 'Lunes',
        };
    }
}
