<?php

namespace App\Http\Controllers\AsignacionYHorarios;
use App\Http\Controllers\Controller;

use App\Models\DocenteMateriaGestion as DMG;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AsignacionController extends Controller
{
    public function __construct()
    {
        // Roles: ADMIN/COORD pueden crear/eliminar; ADMIN/COORD/DECANO consultan
        $this->middleware('role:administrador,admin,coordinador')->only(['create','store','destroy','edit','update']);
        $this->middleware('role:administrador,admin,coordinador,decano')->only(['index']);
    }

    public function index(Request $request)
    {
        $estado = $request->get('estado');
        $gestionId = $request->integer('gestion_id');
        $docenteId = $request->integer('docente_id');

        $asignaciones = DMG::with(['docente.usuario','materia','gestion'])
            ->when($estado, fn($q)=>$q->where('estado', strtoupper($estado)))
            ->when($gestionId, fn($q)=>$q->where('id_gestion', $gestionId))
            ->when($docenteId, fn($q)=>$q->where('id_docente', $docenteId))
            ->orderBy('id_docente_materia_gestion','desc')
            ->paginate(12)
            ->withQueryString();

        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();

        return view('asignaciones.index', compact('asignaciones','estado','gestionId','docenteId','docentes','materias','gestiones'));
    }

    public function create()
    {
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();
        return view('asignaciones.create', compact('docentes','materias','gestiones'));
    }

    public function edit(DMG $asignacione)
    {
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $materias = Materia::orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();
        return view('asignaciones.edit', [
            'dmg' => $asignacione,
            'docentes' => $docentes,
            'materias' => $materias,
            'gestiones' => $gestiones,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_docente' => ['required','integer','exists:docentes,id_docente'],
            'id_materia' => ['required','integer','exists:materias,id_materia'],
            'id_gestion' => ['required','integer','exists:gestiones,id_gestion'],
        ]);

        $exists = DMG::where('id_docente',$data['id_docente'])
            ->where('id_materia',$data['id_materia'])
            ->where('id_gestion',$data['id_gestion'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['general'=>'La asignación ya existe para ese docente/materia/gestión.'])->withInput();
        }

        try {
            $dmg = DMG::create([
                'id_docente' => $data['id_docente'],
                'id_materia' => $data['id_materia'],
                'id_gestion' => $data['id_gestion'],
                'fecha_asignacion' => now()->toDateString(),
                'estado' => 'PENDIENTE',
                'activo' => true,
            ]);
            try {
                DB::table('bitacora')->insert([
                    'id_usuario' => auth()->user()->id_usuario ?? null,
                    'accion' => 'ASIGNAR',
                    'tabla_afectada' => 'docente_materia_gestion',
                    'id_afectado' => (string) $dmg->id_docente_materia_gestion,
                    'ip_origen' => $request->ip(),
                    'descripcion' => 'Asignación de materia a docente (PENDIENTE)',
                    'fecha' => now(),
                ]);
            } catch (\Throwable $e) {}
        } catch (QueryException $e) {
            if ((string)$e->getCode()==='23505') {
                return back()->withErrors(['general'=>'La asignación ya existe.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('asignaciones.index')->with('status','Asignación creada (Pendiente).');
    }

    public function update(Request $request, DMG $asignacione)
    {
        $data = $request->validate([
            'id_docente' => ['required','integer','exists:docentes,id_docente'],
            'id_materia' => ['required','integer','exists:materias,id_materia'],
            'id_gestion' => ['required','integer','exists:gestiones,id_gestion'],
        ]);

        $dup = DMG::where('id_docente',$data['id_docente'])
            ->where('id_materia',$data['id_materia'])
            ->where('id_gestion',$data['id_gestion'])
            ->where('id_docente_materia_gestion','!=',$asignacione->id_docente_materia_gestion)
            ->exists();
        if ($dup) {
            return back()->withErrors(['general'=>'Ya existe una asignación para ese docente/materia/gestión.'])->withInput();
        }

        $asignacione->id_docente = $data['id_docente'];
        $asignacione->id_materia = $data['id_materia'];
        $asignacione->id_gestion = $data['id_gestion'];
        // Resetear aprobación
        $asignacione->estado = 'PENDIENTE';
        $asignacione->aprobado_por = null;
        $asignacione->aprobado_en = null;
        $asignacione->save();

        try {
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => 'EDITAR',
                'tabla_afectada' => 'docente_materia_gestion',
                'id_afectado' => (string) $asignacione->id_docente_materia_gestion,
                'ip_origen' => $request->ip(),
                'descripcion' => 'Reasignación de carga horaria (PENDIENTE)',
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('asignaciones.index')->with('status','Asignación actualizada y marcada como Pendiente.');
    }

    public function destroy(DMG $asignacione)
    {
        // Laravel singulariza resource key como {asignacione}
        if ($asignacione->estado !== 'PENDIENTE') {
            return back()->withErrors(['general'=>'Solo se pueden eliminar asignaciones en estado PENDIENTE.']);
        }
        $id = $asignacione->id_docente_materia_gestion;
        $asignacione->delete();
        try {
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => 'ELIMINAR',
                'tabla_afectada' => 'docente_materia_gestion',
                'id_afectado' => (string) $id,
                'ip_origen' => request()->ip(),
                'descripcion' => 'Se eliminó asignación pendiente',
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {}
        return back()->with('status','Asignación eliminada.');
    }
}
