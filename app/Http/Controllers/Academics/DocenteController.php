<?php

namespace App\Http\Controllers\Academics;
use App\Http\Controllers\Controller;

use App\Models\Docente;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\DocenteMateriaGestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DocenteController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:administrador,admin')->only(['create','store','edit','update','destroy','toggle']);
        $this->middleware('role:administrador,admin,decano')->only(['index','showCarga']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $docentes = Docente::query()
            ->with('usuario')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('usuario', function ($sub) use ($q) {
                    $sub->where('nombre', 'ILIKE', "%$q%")
                        ->orWhere('apellido', 'ILIKE', "%$q%")
                        ->orWhere('correo', 'ILIKE', "%$q%")
                        ->orWhere('telefono', 'ILIKE', "%$q%")
                        ->orWhereRaw("concat(nombre,' ',apellido) ILIKE ?", ["%$q%"]);
                })->orWhere('codigo_docente', 'ILIKE', "%$q%")
                  ->orWhere('profesion', 'ILIKE', "%$q%")
                  ->orWhere('grado_academico', 'ILIKE', "%$q%");
            })
            ->orderBy('id_docente', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('docentes.index', compact('docentes', 'q'));
    }

    public function create()
    {
        return view('docentes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'string', 'min:6'],
            'codigo_docente' => ['nullable', 'string', 'max:30', Rule::unique('docentes','codigo_docente')->whereNot('codigo_docente', null)],
            'profesion' => ['nullable', 'string', 'max:100'],
            'grado_academico' => ['nullable', 'string', 'max:50'],
        ]);

        DB::beginTransaction();
        try {
            $usuario = Usuario::create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo' => $data['correo'],
                'contrasena' => $data['contrasena'],
                'activo' => true,
            ]);

            $docente = Docente::create([
                'id_usuario' => $usuario->id_usuario,
                'codigo_docente' => $data['codigo_docente'] ?? null,
                'profesion' => $data['profesion'] ?? null,
                'grado_academico' => $data['grado_academico'] ?? null,
            ]);

            $rol = Rol::whereRaw('LOWER(nombre)=?', ['docente'])->first();
            if (!$rol) {
                $rol = Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente']);
            }
            $usuario->roles()->syncWithoutDetaching([$rol->id_rol]);
            try {
                DB::table('bitacora')->insert([
                    'id_usuario' => auth()->user()->id_usuario ?? null,
                    'accion' => 'ASIGNAR_ROL',
                    'tabla_afectada' => 'usuario_rol',
                    'id_afectado' => (string) $usuario->id_usuario,
                    'ip_origen' => $request->ip(),
                    'descripcion' => 'Rol DOCENTE asignado',
                    'fecha' => now(),
                ]);
            } catch (\Throwable $e) {}

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            if ((string)$e->getCode()==='23505') {
                return back()->withErrors(['codigo_docente'=>'El código de docente ya existe.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('docentes.index')->with('status', 'Docente registrado exitosamente.');
    }

    public function edit(Docente $docente)
    {
        $docente->load('usuario');
        return view('docentes.edit', compact('docente'));
    }

    public function update(Request $request, Docente $docente)
    {
        $usuario = $docente->usuario;

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', Rule::unique('usuarios','correo')->ignore($usuario->id_usuario,'id_usuario')],
            'codigo_docente' => ['nullable', 'string', 'max:30', Rule::unique('docentes','codigo_docente')->ignore($docente->id_docente,'id_docente')->whereNot('codigo_docente', null)],
            'profesion' => ['nullable', 'string', 'max:100'],
            'grado_academico' => ['nullable', 'string', 'max:50'],
        ]);

        DB::beginTransaction();
        try {
            $usuario->update([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo' => $data['correo'],
            ]);

            $docente->update([
                'codigo_docente' => $data['codigo_docente'] ?? null,
                'profesion' => $data['profesion'] ?? null,
                'grado_academico' => $data['grado_academico'] ?? null,
            ]);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            if ((string)$e->getCode()==='23505') {
                return back()->withErrors(['codigo_docente'=>'El código de docente ya existe.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('docentes.index')->with('status', 'Datos actualizados correctamente.');
    }

    public function destroy(Docente $docente)
    {
        $docente->delete();
        return redirect()->route('docentes.index')->with('status', 'Docente eliminado.');
    }

    public function toggle(Docente $docente)
    {
        $docente->load('usuario');
        if (! $docente->usuario) {
            return back()->withErrors(['general'=>'No se encontró el usuario asociado.']);
        }
        $docente->usuario->activo = ! (bool) $docente->usuario->activo;
        $docente->usuario->save();
        try {
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => $docente->usuario->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'tabla_afectada' => 'docentes',
                'id_afectado' => (string) $docente->id_docente,
                'ip_origen' => request()->ip(),
                'descripcion' => $docente->usuario->activo ? 'Docente activado' : 'Docente desactivado',
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {}
        return back()->with('status', $docente->usuario->activo ? 'Docente activado.' : 'Docente desactivado.');
    }

    public function showCarga(Docente $docente)
    {
        $docente->load(['usuario']);
        $asignaciones = DocenteMateriaGestion::with(['materia','horarios.grupo','gestion'])
            ->where('id_docente', $docente->id_docente)
            ->orderByDesc('id_docente_materia_gestion')
            ->get();

        return view('docentes.carga', compact('docente','asignaciones'));
    }
}

