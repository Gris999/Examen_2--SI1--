<?php

namespace App\Http\Controllers\Assignments;
use App\Http\Controllers\Controller;

use App\Models\Aula;
use App\Models\Docente;
use App\Models\DocenteMateriaGestion as DMG;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    public function __construct()
    {
        // Normaliza acentos de días
        $this->dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    }
    private array $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    private array $modalidades = ['PRESENCIAL','VIRTUAL','HIBRIDA'];

    public function index(Request $request)
    {
        $docenteId = $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');
        $grupoId = $request->integer('grupo_id');
        $aulaId = $request->integer('aula_id');
        $dia = $request->get('dia');

        $query = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula']);
        $docenteActual = $this->docenteActual();
        $soloDocente = $docenteActual !== null;
        if ($soloDocente) {
            $docenteId = $docenteActual->id_docente;
        }

        if ($docenteId) {
            $dmgIds = DMG::where('id_docente', $docenteId)->pluck('id_docente_materia_gestion');
            $query->whereIn('id_docente_materia_gestion', $dmgIds);
        }
        if ($gestionId) {
            $grupoIds = Grupo::where('id_gestion', $gestionId)->pluck('id_grupo');
            $query->whereIn('id_grupo', $grupoIds);
        }
        if ($materiaId) {
            $grupoIds = Grupo::where('id_materia', $materiaId)->pluck('id_grupo');
            $query->whereIn('id_grupo', $grupoIds);
        }
        if ($grupoId) {
            $query->where('id_grupo', $grupoId);
        }
        if ($aulaId) {
            $query->where('id_aula', $aulaId);
        }
        if ($dia) {
            $query->where('dia', $dia);
        }

        $horarios = $query->orderBy('id_horario','desc')->paginate(10)->withQueryString();

        // Resumen para el modal de generación automática
        $dmgFilter = DMG::where('estado','APROBADA')
            ->when($docenteId, fn($q)=>$q->where('id_docente',$docenteId))
            ->when($gestionId, fn($q)=>$q->where('id_gestion',$gestionId))
            ->when($materiaId, fn($q)=>$q->where('id_materia',$materiaId))
            ->get();
        $aproCount = $dmgFilter->count();
        $materias = $dmgFilter->pluck('id_materia')->unique()->values();
        $gestiones = $dmgFilter->pluck('id_gestion')->unique()->values();
        $toProcess = 0;
        if ($materias->isNotEmpty() || $gestiones->isNotEmpty()) {
            $toProcess = Grupo::when($materias->isNotEmpty(), fn($q)=>$q->whereIn('id_materia',$materias))
                ->when($gestiones->isNotEmpty(), fn($q)=>$q->whereIn('id_gestion',$gestiones))
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('horarios')
                        ->whereColumn('horarios.id_grupo','grupos.id_grupo');
                })
                ->count();
        }

        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('id_grupo','desc')->get();
        $aulas = Aula::orderBy('nombre')->get();
        $dias = $this->dias;
        $horariosHoy = $soloDocente ? $this->horariosDocenteHoy($docenteActual) : collect();
        $heading = $soloDocente ? 'Mis Horarios' : 'Horarios';
        $subheading = $soloDocente
            ? 'Visualiza únicamente tu carga aprobada. Mantén este módulo como referencia para generar QR y registrar asistencia.'
            : 'Crea y administra horarios aprobados';

        return view('horarios.index', compact('horarios','docentes','gestiones','materias','grupos','aulas','dias','docenteId','gestionId','materiaId','grupoId','aulaId','dia','aproCount','toProcess','soloDocente','horariosHoy','heading','subheading'));
    }

    public function create()
    {
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $grupos = Grupo::with(['materia','gestion'])->orderBy('id_grupo','desc')->get();
        $aulas = Aula::orderBy('nombre')->get();
        $dias = $this->dias;
        $modalidades = $this->modalidades;
        $docente = $this->docenteActual();
        $horariosHoy = $this->horariosDocenteHoy($docente);
        return view('horarios.create', compact('docentes','grupos','aulas','dias','modalidades','docente','horariosHoy'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_docente' => ['required','integer','exists:docentes,id_docente'],
            'id_grupo' => ['required','integer','exists:grupos,id_grupo'],
            'id_aula' => ['nullable','integer','exists:aulas,id_aula'],
            'dia' => ['required','string'],
            'hora_inicio' => ['required','date_format:H:i'],
            'hora_fin' => ['required','date_format:H:i','after:hora_inicio'],
            'modalidad' => ['required','in:PRESENCIAL,VIRTUAL,HIBRIDA'],
            'virtual_plataforma' => ['nullable','string','max:50'],
            'virtual_enlace' => ['nullable','string'],
            'observacion' => ['nullable','string'],
        ]);

        $grupo = Grupo::with(['materia','gestion'])->findOrFail($data['id_grupo']);

        $dmg = DMG::where('id_docente', $data['id_docente'])
            ->where('id_materia', $grupo->id_materia)
            ->where('id_gestion', $grupo->id_gestion)
            ->first();
        if (!$dmg || $dmg->estado !== 'APROBADA') {
            return back()->withErrors(['id_docente' => 'La asignación Docente–Materia–Gestión no está aprobada. Revise CU6/CU7.'])->withInput();
        }

        $this->validateOverlap($data, $dmg->id_docente_materia_gestion);

        try {
        Horario::create([
            'id_docente_materia_gestion' => $dmg->id_docente_materia_gestion,
            'id_grupo' => $grupo->id_grupo,
            'id_aula' => $data['id_aula'] ?? null,
            'dia' => $data['dia'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'modalidad' => $data['modalidad'],
            'virtual_plataforma' => $data['virtual_plataforma'] ?? null,
            'virtual_enlace' => $data['virtual_enlace'] ?? null,
            'observacion' => $data['observacion'] ?? null,
            'estado' => 'APROBADA',
        ]);
        } catch (QueryException $e) {
            $code = (string) $e->getCode();
            if ($code === '23P01' || $code === '23505') {
                return back()->withErrors(['general' => 'No se pudo crear: conflicto de horario (docente/aula/grupo). Verifique solapamientos.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('horarios.index')->with('status','Horario creado.');
    }

    public function edit(Horario $horario)
    {
        $horario->load(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula']);
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $grupos = Grupo::with(['materia','gestion'])->orderBy('id_grupo','desc')->get();
        $aulas = Aula::orderBy('nombre')->get();
        $dias = $this->dias;
        $modalidades = $this->modalidades;
        return view('horarios.edit', compact('horario','docentes','grupos','aulas','dias','modalidades'));
    }

    public function update(Request $request, Horario $horario)
    {
        $data = $request->validate([
            'id_docente' => ['required','integer','exists:docentes,id_docente'],
            'id_grupo' => ['required','integer','exists:grupos,id_grupo'],
            'id_aula' => ['nullable','integer','exists:aulas,id_aula'],
            'dia' => ['required','string'],
            'hora_inicio' => ['required','date_format:H:i'],
            'hora_fin' => ['required','date_format:H:i','after:hora_inicio'],
            'modalidad' => ['required','in:PRESENCIAL,VIRTUAL,HIBRIDA'],
            'virtual_plataforma' => ['nullable','string','max:50'],
            'virtual_enlace' => ['nullable','string'],
            'observacion' => ['nullable','string'],
        ]);

        $grupo = Grupo::with(['materia','gestion'])->findOrFail($data['id_grupo']);

        $dmg = DMG::where('id_docente', $data['id_docente'])
            ->where('id_materia', $grupo->id_materia)
            ->where('id_gestion', $grupo->id_gestion)
            ->first();
        if (!$dmg || $dmg->estado !== 'APROBADA') {
            return back()->withErrors(['id_docente' => 'La asignación Docente–Materia–Gestión no está aprobada. Revise CU6/CU7.'])->withInput();
        }

        $this->validateOverlap($data, $dmg->id_docente_materia_gestion, $horario->id_horario);

        try {
        $horario->update([
            'id_docente_materia_gestion' => $dmg->id_docente_materia_gestion,
            'id_grupo' => $grupo->id_grupo,
            'id_aula' => $data['id_aula'] ?? null,
            'dia' => $data['dia'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'modalidad' => $data['modalidad'],
            'virtual_plataforma' => $data['virtual_plataforma'] ?? null,
            'virtual_enlace' => $data['virtual_enlace'] ?? null,
            'observacion' => $data['observacion'] ?? null,
            'estado' => 'APROBADA',
        ]);
        } catch (QueryException $e) {
            $code = (string) $e->getCode();
            if ($code === '23P01' || $code === '23505') {
                return back()->withErrors(['general' => 'No se pudo actualizar: conflicto de horario (docente/aula/grupo). Verifique solapamientos.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('horarios.index')->with('status','Horario actualizado.');
    }

    public function destroy(Horario $horario)
    {
        $horario->delete();
        return redirect()->route('horarios.index')->with('status','Horario eliminado.');
    }

    // CU8: devuelve disponibilidad de aulas (JSON) para día y rango
    public function getDisponibilidad(Request $request)
    {
        $data = $request->validate([
            'dia' => ['required','string'],
            'hora_inicio' => ['required','date_format:H:i'],
            'hora_fin' => ['required','date_format:H:i','after:hora_inicio'],
        ]);

        $mapDias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
        $diaParam = $data['dia'];
        $dia = array_key_exists((int)$diaParam, $mapDias) ? $mapDias[(int)$diaParam] : $diaParam;

        $ocupadas = Horario::query()
            ->select('id_aula')
            ->whereNotNull('id_aula')
            ->where('dia', $dia)
            ->where(function($q) use ($data){
                $q->where('hora_inicio','<',$data['hora_fin'])
                  ->where('hora_fin','>',$data['hora_inicio']);
            })
            ->pluck('id_aula')
            ->filter()
            ->unique()
            ->values();

        $aulas = Aula::orderBy('codigo')->get(['id_aula','codigo','nombre','capacidad','ubicacion']);
        $disponibles = $aulas->whereNotIn('id_aula', $ocupadas)->values();

        return response()->json([
            'dia' => $dia,
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'ocupadas' => $aulas->whereIn('id_aula', $ocupadas)->values(),
            'disponibles' => $disponibles,
        ]);
    }

    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if (!$user) { return null; }
        if (!$user->roles()->where('nombre','docente')->exists()) { return null; }
        return Docente::where('id_usuario', $user->id_usuario ?? 0)->first();
    }

    private function horariosDocenteHoy(?Docente $docente)
    {
        if (!$docente) { return collect(); }
        $ids = DMG::where('id_docente', $docente->id_docente)->pluck('id_docente_materia_gestion');
        if ($ids->isEmpty()) { return collect(); }
        $dow = $this->dowName(\Carbon\Carbon::now('America/La_Paz')->dayOfWeekIso);
        return Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->where('dia', $dow)
            ->whereIn('id_docente_materia_gestion', $ids)
            ->orderBy('hora_inicio','asc')
            ->get();
    }

    // CU8: generación automática simple de horarios sin solape
    public function generateAutomatic(Request $request)
    {
        $gestionId = $request->integer('gestion_id');
        $docenteId = $request->integer('docente_id');
        $materiaId = $request->integer('materia_id');

        // Opcional: seleccionar días y slots específicos
        $diasSel = $request->input('dias'); // array de strings
        $slotsSel = $request->input('slots'); // array de 'HH:MM-HH:MM'

        // Validación suave: si vienen, validar valores conocidos
        $diasValidos = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        if (is_array($diasSel)) {
            $diasSel = array_values(array_intersect($diasSel, $diasValidos));
        } else { $diasSel = []; }

        $slots = [];
        if (is_array($slotsSel) && count($slotsSel)) {
            foreach ($slotsSel as $pair) {
                if (!is_string($pair) || !str_contains($pair,'-')) continue;
                [$i,$f] = explode('-', $pair, 2);
                $i = trim($i); $f = trim($f);
                // Formato HH:MM básico
                if (!preg_match('/^\d{2}:\d{2}$/', $i) || !preg_match('/^\d{2}:\d{2}$/', $f)) continue;
                if ($i >= $f) continue;
                $slots[] = [$i,$f];
            }
        }
        $created = 0; $skipped = 0;

        // Validación: debe haber al menos un día y un bloque
        if (count($diasSel) === 0 || count($slots) === 0) {
            return back()->withErrors(['general' => 'Selecciona al menos un día y un bloque horario para generar.']);
        }

        $aulas = Aula::orderBy('codigo')->get();

        $dmgQuery = DMG::where('estado','APROBADA');
        if ($gestionId) { $dmgQuery->where('id_gestion', $gestionId); }
        if ($docenteId) { $dmgQuery->where('id_docente', $docenteId); }
        if ($materiaId) { $dmgQuery->where('id_materia', $materiaId); }
        $dmgs = $dmgQuery->get();

        foreach ($dmgs as $dmg) {
            $grupos = Grupo::where('id_materia', $dmg->id_materia)
                ->where('id_gestion', $dmg->id_gestion)
                ->get();

            foreach ($grupos as $grupo) {
                if (Horario::where('id_grupo', $grupo->id_grupo)->exists()) { $skipped++; continue; }

                $placed = false;
                $diasLoop = $diasSel;
                foreach ($diasLoop as $dia) {
                    foreach ($slots as [$ini,$fin]) {
                        $docDmgIds = DMG::where('id_docente', $dmg->id_docente)->pluck('id_docente_materia_gestion');
                        $confDoc = Horario::whereIn('id_docente_materia_gestion', $docDmgIds)
                            ->where('dia', $dia)
                            ->where(function($q) use ($ini,$fin){
                                $q->where('hora_inicio','<',$fin)->where('hora_fin','>',$ini);
                            })->exists();
                        if ($confDoc) { continue; }

                        $confGrupo = Horario::where('id_grupo', $grupo->id_grupo)
                            ->where('dia', $dia)
                            ->where(function($q) use ($ini,$fin){
                                $q->where('hora_inicio','<',$fin)->where('hora_fin','>',$ini);
                            })->exists();
                        if ($confGrupo) { continue; }

                        $aulaLibre = $aulas->first(function($aula) use ($dia,$ini,$fin){
                            return !Horario::where('id_aula', $aula->id_aula)
                                ->where('dia', $dia)
                                ->where(function($q) use ($ini,$fin){
                                    $q->where('hora_inicio','<',$fin)->where('hora_fin','>',$ini);
                                })->exists();
                        });

                        if ($aulaLibre) {
                            Horario::create([
                                'id_docente_materia_gestion' => $dmg->id_docente_materia_gestion,
                                'id_grupo' => $grupo->id_grupo,
                                'id_aula' => $aulaLibre->id_aula,
                                'dia' => $dia,
                                'hora_inicio' => $ini,
                                'hora_fin' => $fin,
                                'modalidad' => 'PRESENCIAL',
                                'estado' => 'APROBADA',
                            ]);
                            $created++; $placed = true; break 2;
                        }
                    }
                }
                if (!$placed) { $skipped++; }
            }
        }

        try { \Illuminate\Support\Facades\DB::table('bitacora')->insert([
            'id_usuario' => auth()->user()->id_usuario ?? null,
            'accion' => 'AUTOGENERAR',
            'tabla_afectada' => 'horarios',
            'id_afectado' => null,
            'ip_origen' => request()->ip(),
            'descripcion' => 'Horarios generados automáticamente',
            'fecha' => now(),
        ]); } catch (\Throwable $e) {}

        return redirect()->route('horarios.index')->with('status', "Auto-generación: creados $created, omitidos $skipped.");
    }

    private function validateOverlap(array $data, int $dmgId, ?int $ignoreHorarioId = null): void
    {
        $docDmgIds = DMG::where('id_docente', DMG::find($dmgId)->id_docente)
            ->pluck('id_docente_materia_gestion');

        $conflictDoc = Horario::whereIn('id_docente_materia_gestion', $docDmgIds)
            ->where('dia', $data['dia'])
            ->when($ignoreHorarioId, fn($q)=>$q->where('id_horario','!=',$ignoreHorarioId))
            ->where(function($q) use ($data) {
                $q->where('hora_inicio','<',$data['hora_fin'])
                  ->where('hora_fin','>',$data['hora_inicio']);
            })
            ->exists();
        if ($conflictDoc) {
            abort(back()->withErrors(['hora_inicio' => 'El docente ya tiene un horario que se solapa.'])->getTargetUrl());
        }

        if (!empty($data['id_aula'])) {
            $conflictAula = Horario::where('id_aula', $data['id_aula'])
                ->where('dia', $data['dia'])
                ->when($ignoreHorarioId, fn($q)=>$q->where('id_horario','!=',$ignoreHorarioId))
                ->where(function($q) use ($data) {
                    $q->where('hora_inicio','<',$data['hora_fin'])
                      ->where('hora_fin','>',$data['hora_inicio']);
                })
                ->exists();
            if ($conflictAula) {
                abort(back()->withErrors(['id_aula' => 'El aula ya está ocupada en ese rango.'])->getTargetUrl());
            }
        }

        $conflictGrupo = Horario::where('id_grupo', $data['id_grupo'])
            ->where('dia', $data['dia'])
            ->when($ignoreHorarioId, fn($q)=>$q->where('id_horario','!=',$ignoreHorarioId))
            ->where(function($q) use ($data) {
                $q->where('hora_inicio','<',$data['hora_fin'])
                  ->where('hora_fin','>',$data['hora_inicio']);
            })
            ->exists();
        if ($conflictGrupo) {
            abort(back()->withErrors(['id_grupo' => 'El grupo ya tiene un horario que se solapa.'])->getTargetUrl());
        }
    }
}
