<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Docente;
use App\Models\DocenteMateriaGestion as DMG;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Gestion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultaHorarioController extends Controller
{
    private array $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

    public function index(Request $request)
    {
        $docenteId = $request->integer('docente_id');
        $grupoId   = $request->integer('grupo_id');
        $aulaId    = $request->integer('aula_id');
        $gestionId = $request->integer('gestion_id');
        $semana    = $request->date('semana'); // opcional, solo para título

        $query = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula']);

        if ($docenteId) {
            $dmgIds = DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion');
            $query->whereIn('id_docente_materia_gestion', $dmgIds);
        }
        if ($grupoId) {
            $query->where('id_grupo', $grupoId);
        }
        if ($gestionId) {
            $grupoIds = Grupo::where('id_gestion', $gestionId)->pluck('id_grupo');
            $query->whereIn('id_grupo', $grupoIds);
        }
        if ($aulaId) {
            $query->where('id_aula', $aulaId);
        }

        $horarios = $query->orderBy('dia')->orderBy('hora_inicio')->get();

        // Mapa por día
        $porDia = collect($this->dias)->mapWithKeys(fn($d)=>[$d=>[]])->toArray();
        foreach ($horarios as $h) {
            if (!isset($porDia[$h->dia])) { $porDia[$h->dia] = []; }
            $porDia[$h->dia][] = $h;
        }

        // Slots de tiempo inferidos (30 min)
        $min = $horarios->min('hora_inicio');
        $max = $horarios->max('hora_fin');
        if (!$min || !$max) { $min = '07:00:00'; $max = '22:00:00'; }
        $slots = $this->buildSlots($min, $max, 30);

        // Listas para filtros
        $docentes  = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $grupos    = Grupo::with(['materia','gestion'])->orderBy('id_grupo','desc')->get();
        $aulas     = Aula::orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();

        return view('consultas.horarios.index', [
            'title'     => 'Consulta de Horarios',
            'docentes'  => $docentes,
            'grupos'    => $grupos,
            'aulas'     => $aulas,
            'gestiones' => $gestiones,
            'dias'      => $this->dias,
            'slots'     => $slots,
            'porDia'    => $porDia,
            'docenteId' => $docenteId,
            'grupoId'   => $grupoId,
            'aulaId'    => $aulaId,
            'gestionId' => $gestionId,
            'semana'    => $semana,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $docenteId = $request->integer('docente_id');
        $grupoId   = $request->integer('grupo_id');
        $aulaId    = $request->integer('aula_id');
        $gestionId = $request->integer('gestion_id');

        $query = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula']);
        if ($docenteId) { $query->whereIn('id_docente_materia_gestion', DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion')); }
        if ($grupoId)   { $query->where('id_grupo', $grupoId); }
        if ($gestionId) { $query->whereIn('id_grupo', Grupo::where('id_gestion',$gestionId)->pluck('id_grupo')); }
        if ($aulaId)    { $query->where('id_aula', $aulaId); }
        $rows = $query->orderBy('dia')->orderBy('hora_inicio')->get();

        $filename = 'horarios_export.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function() use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 + separador para Excel
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=,\r\n");

            fputcsv($out, ['Día','Inicio','Fin','Materia','Grupo','Gestión','Aula','Docente']);
            foreach ($rows as $r) {
                $doc = optional(optional($r->docenteMateriaGestion)->docente)->usuario;
                fputcsv($out, [
                    $this->fixCsv((string)($r->dia)),
                    substr((string)($r->hora_inicio),0,5),
                    substr((string)($r->hora_fin),0,5),
                    $this->fixCsv((string) optional($r->grupo->materia)->nombre),
                    $this->fixCsv((string) ($r->grupo->nombre_grupo ?? '')),
                    $this->fixCsv((string) optional($r->grupo->gestion)->codigo),
                    $this->fixCsv((string) optional($r->aula)->nombre),
                    $this->fixCsv(trim((string)(($doc->nombre ?? '').' '.($doc->apellido ?? '')))),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    private function buildSlots(string $min, string $max, int $stepMinutes = 60): array
    {
        $start = strtotime($min);
        $end   = strtotime($max);
        if ($start === false || $end === false) {
            $start = strtotime('07:00:00');
            $end   = strtotime('22:00:00');
        }
        if ($start > $end) { [$start,$end] = [$end,$start]; }
        $start = floor($start / ($stepMinutes*60)) * ($stepMinutes*60);
        $end   = ceil($end   / ($stepMinutes*60)) * ($stepMinutes*60);

        $slots = [];
        for ($t = $start; $t <= $end; $t += $stepMinutes*60) {
            $slots[] = date('H:i', $t);
        }
        return $slots;
    }

    private function fixCsv(string $s): string
    {
        // Corrige secuencias comunes de doble codificación (Ã¡, Ã©, Ã­, Ã³, Ãº, Ã±, ...)
        $map = [
            'Ã¡'=>'á','Ã©'=>'é','Ãí'=>'í','Ã³'=>'ó','Ãº'=>'ú',
            'ÃÁ'=>'Á','Ã‰'=>'É','ÃÍ'=>'Í','Ã“'=>'Ó','Ãš'=>'Ú',
            'Ã±'=>'ñ','Ã‘'=>'Ñ','Ã¼'=>'ü','Ãœ'=>'Ü',
        ];
        $s = strtr($s, $map);
        return utf8_encode(utf8_decode($s));
    }
}

