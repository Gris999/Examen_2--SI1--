<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Gestion;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Docente;
use App\Models\DocenteMateriaGestion;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    public function __construct()
    {
        // CU4: Permisos
        // - ADMIN y COORDINADOR pueden crear/editar/eliminar y asignar docentes
        // - DECANO solo lectura (index y listado de docentes)
        $this->middleware('role:administrador,admin,coordinador')->only([
            'create','store','edit','update','destroy','addDocente','removeDocente'
        ]);
        $this->middleware('role:administrador,admin,coordinador,decano')->only([
            'index','docentes'
        ]);
    }
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $facultadId = $request->integer('facultad_id');
        $carreraId = $request->integer('carrera_id');
        $gestionId = $request->integer('gestion_id');

        $grupos = Grupo::query()
            ->with(['materia.carrera', 'gestion'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre_grupo', 'ILIKE', "%$q%")
                    ->orWhereHas('materia', function ($sub) use ($q) {
                        $sub->where('nombre', 'ILIKE', "%$q%")
                            ->orWhere('codigo', 'ILIKE', "%$q%");
                    });
            })
            ->when($gestionId, fn($query)=>$query->where('id_gestion', $gestionId))
            ->when($carreraId, fn($query)=>$query->whereHas('materia', fn($sub)=>$sub->where('id_carrera', $carreraId)))
            ->when($facultadId && !$carreraId, function ($query) use ($facultadId) {
                $query->whereHas('materia.carrera', fn($sub)=>$sub->where('id_facultad', $facultadId));
            })
            ->orderBy('id_grupo', 'desc')
            ->paginate(10)
            ->withQueryString();

        $facultades = Facultad::orderBy('nombre')->get(['id_facultad','nombre','sigla']);
        $carreras = Carrera::when($facultadId, fn($q)=>$q->where('id_facultad', $facultadId))
            ->orderBy('nombre')->get(['id_carrera','id_facultad','nombre','sigla']);
        $gestiones = Gestion::orderBy('fecha_inicio', 'desc')->get(['id_gestion','codigo','activo']);

        return view('grupos.index', compact('grupos','q','facultades','carreras','gestiones','facultadId','carreraId','gestionId'));
    }

    public function create()
    {
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get(['id_gestion','codigo','activo']);
        $facultades = Facultad::orderBy('nombre')->get(['id_facultad','nombre','sigla']);
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera','id_facultad','nombre','sigla']);
        $materias = Materia::orderBy('nombre')->get(['id_materia','id_carrera','nombre','codigo']);
        return view('grupos.create', compact('gestiones','facultades','carreras','materias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_gestion' => ['required','integer','exists:gestiones,id_gestion'],
            'id_materia' => ['required','integer','exists:materias,id_materia'],
            'nombre_grupo' => ['required','string','max:10'],
            'cupo' => ['nullable','integer','min:1'],
        ]);

        // evitar duplicados: misma materia + gestion + nombre
        $exists = Grupo::where('id_gestion', $data['id_gestion'])
            ->where('id_materia', $data['id_materia'])
            ->where('nombre_grupo', $data['nombre_grupo'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['nombre_grupo' => 'Ya existe un grupo con ese nombre para la materia y gestión seleccionadas.'])->withInput();
        }

        Grupo::create($data);
        return redirect()->route('grupos.index')->with('status','Grupo creado correctamente.');
    }

    public function edit(Grupo $grupo)
    {
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get(['id_gestion','codigo','activo']);
        $facultades = Facultad::orderBy('nombre')->get(['id_facultad','nombre','sigla']);
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera','id_facultad','nombre','sigla']);
        $materias = Materia::orderBy('nombre')->get(['id_materia','id_carrera','nombre','codigo']);
        return view('grupos.edit', compact('grupo','gestiones','facultades','carreras','materias'));
    }

    public function update(Request $request, Grupo $grupo)
    {
        $data = $request->validate([
            'id_gestion' => ['required','integer','exists:gestiones,id_gestion'],
            'id_materia' => ['required','integer','exists:materias,id_materia'],
            'nombre_grupo' => ['required','string','max:10'],
            'cupo' => ['nullable','integer','min:1'],
        ]);

        $exists = Grupo::where('id_gestion', $data['id_gestion'])
            ->where('id_materia', $data['id_materia'])
            ->where('nombre_grupo', $data['nombre_grupo'])
            ->where('id_grupo','!=',$grupo->id_grupo)
            ->exists();
        if ($exists) {
            return back()->withErrors(['nombre_grupo' => 'Ya existe un grupo con ese nombre para la materia y gestión seleccionadas.'])->withInput();
        }

        $grupo->update($data);
        return redirect()->route('grupos.index')->with('status','Grupo actualizado correctamente.');
    }

    public function destroy(Grupo $grupo)
    {
        // Evitar borrar si existen horarios asociados
        $hasHorarios = Horario::where('id_grupo', $grupo->id_grupo)->exists();
        if ($hasHorarios) {
            return back()->withErrors(['general' => 'No se puede eliminar el grupo porque tiene horarios asociados.']);
        }
        $grupo->delete();
        return redirect()->route('grupos.index')->with('status','Grupo eliminado.');
    }

    // Asignar docentes (crea registros en docente_materia_gestion para la materia + gestión del grupo)
    public function docentes(Grupo $grupo, Request $request)
    {
        $grupo->load(['materia','gestion']);

        $q = trim((string) $request->get('q',''));

        $asignados = DocenteMateriaGestion::with('docente.usuario')
            ->where('id_materia', $grupo->id_materia)
            ->where('id_gestion', $grupo->id_gestion)
            ->orderBy('id_docente_materia_gestion','desc')
            ->get();
        $tieneAsignado = $asignados->count() >= 1;

        $docentes = Docente::with('usuario')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('usuario', function ($sub) use ($q) {
                    $sub->where('nombre', 'ILIKE', "%$q%")
                        ->orWhere('apellido', 'ILIKE', "%$q%")
                        ->orWhere('correo', 'ILIKE', "%$q%");
                })->orWhere('codigo_docente','ILIKE', "%$q%");
            })
            ->orderBy('id_docente','desc')
            ->paginate(10)
            ->withQueryString();

        return view('grupos.docentes', compact('grupo','asignados','docentes','q','tieneAsignado'));
    }

    public function addDocente(Grupo $grupo, Request $request)
    {
        $data = $request->validate([
            'id_docente' => ['required','integer','exists:docentes,id_docente'],
        ]);

        // Solo 1 docente por grupo (materia+gestión del grupo)
        $yaTiene = DocenteMateriaGestion::where('id_materia', $grupo->id_materia)
            ->where('id_gestion', $grupo->id_gestion)
            ->exists();
        if ($yaTiene) {
            return back()->withErrors(['general' => 'Este grupo ya tiene un docente asignado. Quite el docente antes de asignar otro.']);
        }

        $exists = DocenteMateriaGestion::where('id_docente', $data['id_docente'])
            ->where('id_materia', $grupo->id_materia)
            ->where('id_gestion', $grupo->id_gestion)
            ->exists();
        if(!$exists){
            DocenteMateriaGestion::create([
                'id_docente' => $data['id_docente'],
                'id_materia' => $grupo->id_materia,
                'id_gestion' => $grupo->id_gestion,
                'fecha_asignacion' => now()->toDateString(),
                'estado' => 'PENDIENTE',
                'activo' => true,
            ]);
        }

        return redirect()->route('grupos.docentes', $grupo)->with('status','Docente asignado a la materia/gestión.');
    }

    public function removeDocente(Grupo $grupo, DocenteMateriaGestion $dmg)
    {
        // Solo si pertenece a la misma materia/gestión del grupo
        if ($dmg->id_materia != $grupo->id_materia || $dmg->id_gestion != $grupo->id_gestion) {
            return back()->withErrors(['general' => 'La asignación no pertenece a este grupo.']);
        }
        // Evitar eliminar si hay horarios que usan este DMG
        $inUse = Horario::where('id_docente_materia_gestion', $dmg->id_docente_materia_gestion)->exists();
        if ($inUse) {
            return back()->withErrors(['general' => 'No se puede quitar porque existen horarios asociados.']);
        }
        $dmg->delete();
        return redirect()->route('grupos.docentes', $grupo)->with('status','Asignación eliminada.');
    }
}
