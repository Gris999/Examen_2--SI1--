<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\DocenteMateriaGestion as DMG;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CargaHorariaController extends Controller
{
    private array $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    private array $modalidades = ['PRESENCIAL','VIRTUAL','HIBRIDA'];

    public function __construct()
    {
        // Normaliza acentos de días
        $this->dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    }

    public function index(Request $request)
    {
        $docenteId = $request->integer('docente_id');
        $gestionId = $request->integer('gestion_id');
        $materiaId = $request->integer('materia_id');
        $grupoId = $request->integer('grupo_id');
        $dia = $request->get('dia');

        $query = Horario::query()
            ->with(['grupo.materia','grupo.gestion','grupo','docenteMateriaGestion.docente.usuario','aula']);

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
        if ($dia) {
            $query->where('dia', $dia);
        }

        $horarios = $query->orderBy('id_horario','desc')->paginate(10)->withQueryString();

        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('id_grupo','desc')->get();
        $dias = $this->dias;

        return view('carga.index', compact('horarios','docentes','gestiones','materias','grupos','dias','docenteId','gestionId','materiaId','grupoId','dia'));
    }

    public function create()
    {
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $grupos = Grupo::with(['materia','gestion'])->orderBy('id_grupo','desc')->get();
        $aulas = Aula::orderBy('nombre')->get();
        $dias = $this->dias;
        $modalidades = $this->modalidades;
        return view('carga.create', compact('docentes','grupos','aulas','dias','modalidades'));
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

        // Requiere asignación aprobada (CU7) antes de crear horario
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
            // Si la DMG está aprobada, el horario queda aprobado automáticamente
            'estado' => 'APROBADA',
        ]);
        } catch (QueryException $e) {
            $code = (string) $e->getCode();
            if ($code === '23P01' || $code === '23505') {
                return back()->withErrors(['general' => 'No se pudo registrar la carga: conflicto de horario (docente/aula/grupo). Verifique solapamientos.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('carga.index')->with('status','Carga horaria registrada.');
    }

    public function edit(Horario $cargum)
    {
        // Resource key: {cargum} por convención de Laravel al pluralizar; mantenemos variable
        $horario = $cargum->load(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula']);
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $grupos = Grupo::with(['materia','gestion'])->orderBy('id_grupo','desc')->get();
        $aulas = Aula::orderBy('nombre')->get();
        $dias = $this->dias;
        $modalidades = $this->modalidades;
        return view('carga.edit', compact('horario','docentes','grupos','aulas','dias','modalidades'));
    }

    public function update(Request $request, Horario $cargum)
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

        $this->validateOverlap($data, $dmg->id_docente_materia_gestion, $cargum->id_horario);

        try {
        $cargum->update([
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
            // Mantener consistente: aprobado por defecto si DMG es aprobada
            'estado' => 'APROBADA',
        ]);
        } catch (QueryException $e) {
            $code = (string) $e->getCode();
            if ($code === '23P01' || $code === '23505') {
                return back()->withErrors(['general' => 'No se pudo actualizar la carga: conflicto de horario (docente/aula/grupo). Verifique solapamientos.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('carga.index')->with('status','Carga horaria actualizada.');
    }

    public function destroy(Horario $cargum)
    {
        $cargum->delete();
        return redirect()->route('carga.index')->with('status','Registro eliminado.');
    }

    private function validateOverlap(array $data, int $dmgId, ?int $ignoreHorarioId = null): void
    {
        // Docente overlap: cualquier horario del mismo docente en el mismo día
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

        // Aula overlap (si hay aula)
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

        // Grupo overlap
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
