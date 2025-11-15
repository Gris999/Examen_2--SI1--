<?php

namespace App\Http\Controllers\Academics;
use App\Http\Controllers\Controller;

use App\Models\Materia;
use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MateriaController extends Controller
{
    public function __construct()
    {
        // CU3: ADMIN puede CRUD; DECANO solo lectura (index)
        $this->middleware('role:administrador,admin')->only(['create','store','edit','update','destroy']);
        $this->middleware('role:administrador,admin,decano')->only(['index']);
    }
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $facultadId = $request->integer('facultad_id');
        $carreraId = $request->integer('carrera_id');

        $materias = Materia::query()
            ->with('carrera')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre', 'ILIKE', "%$q%")
                      ->orWhere('codigo', 'ILIKE', "%$q%")
                      ->orWhere('descripcion', 'ILIKE', "%$q%")
                      ->orWhereHas('carrera', function ($sub) use ($q) {
                          $sub->where('nombre', 'ILIKE', "%$q%")
                              ->orWhere('sigla', 'ILIKE', "%$q%");
                      });
            })
            ->when($carreraId, function ($query) use ($carreraId) {
                $query->where('id_carrera', $carreraId);
            })
            ->when($facultadId && !$carreraId, function ($query) use ($facultadId) {
                $query->whereHas('carrera', function ($sub) use ($facultadId) {
                    $sub->where('id_facultad', $facultadId);
                });
            })
            ->orderBy('id_materia', 'desc')
            ->paginate(10)
            ->withQueryString();

        $facultades = Facultad::orderBy('nombre')->get(['id_facultad','nombre','sigla']);
        $carreras = Carrera::when($facultadId, fn($q)=>$q->where('id_facultad', $facultadId))
            ->orderBy('nombre')->get(['id_carrera','id_facultad','nombre','sigla']);

        return view('materias.index', compact('materias', 'q', 'facultades', 'carreras', 'facultadId', 'carreraId'));
    }

    public function create()
    {
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera','nombre','sigla']);
        return view('materias.create', compact('carreras'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_carrera' => ['required', 'integer', 'exists:carreras,id_carrera'],
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'string', 'max:40', Rule::unique('materias', 'codigo')],
            'carga_horaria' => ['required', 'integer', 'min:1'],
            'descripcion' => ['nullable', 'string'],
        ]);

        Materia::create($data);
        return redirect()->route('materias.index')->with('status', 'Materia creada correctamente.');
    }

    public function edit(Materia $materia)
    {
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera','nombre','sigla']);
        return view('materias.edit', compact('materia','carreras'));
    }

    public function update(Request $request, Materia $materia)
    {
        $data = $request->validate([
            'id_carrera' => ['required', 'integer', 'exists:carreras,id_carrera'],
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'string', 'max:40', Rule::unique('materias', 'codigo')->ignore($materia->id_materia, 'id_materia')],
            'carga_horaria' => ['required', 'integer', 'min:1'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $materia->update($data);
        return redirect()->route('materias.index')->with('status', 'Materia actualizada correctamente.');
    }

    public function destroy(Materia $materia)
    {
        $materia->delete();
        return redirect()->route('materias.index')->with('status', 'Materia eliminada.');
    }
}
