<?php

namespace App\Http\Controllers\Academics;
use App\Http\Controllers\Controller;

use App\Models\Aula;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AulaController extends Controller
{
    private array $tipos = ['TEORIA','LABORATORIO','AUDITORIO','VIRTUAL'];

    public function __construct()
    {
        $this->middleware('role:administrador,admin,coordinador')->only(['create','store','edit','update','destroy']);
        $this->middleware('role:administrador,admin,coordinador,decano,docente')->only(['index','disponibilidad']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $tipo = $request->get('tipo');

        $aulas = Aula::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('codigo', 'ILIKE', "%$q%")
                      ->orWhere('nombre', 'ILIKE', "%$q%")
                      ->orWhere('tipo', 'ILIKE', "%$q%")
                      ->orWhere('ubicacion', 'ILIKE', "%$q%");
            })
            ->when($tipo, fn($query)=>$query->where('tipo', $tipo))
            ->orderBy('id_aula','desc')
            ->paginate(10)
            ->withQueryString();

        $usoMap = Horario::selectRaw('id_aula, COUNT(*) as c')
            ->whereNotNull('id_aula')
            ->groupBy('id_aula')
            ->pluck('c','id_aula');

        $tipos = $this->tipos;
        return view('aulas.index', compact('aulas','q','tipo','tipos','usoMap'));
    }

    public function create()
    {
        $tipos = $this->tipos;
        return view('aulas.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required','string','max:50','unique:aulas,codigo'],
            'nombre' => ['required','string','max:120'],
            'tipo' => ['required','string','in:TEORIA,LABORATORIO,VIRTUAL,AUDITORIO'],
            // capacidad requerida salvo aulas virtuales
            'capacidad' => ['required','integer','min:1'],
            'ubicacion' => ['nullable','string'],
        ]);

        Aula::create($data);
        return redirect()->route('aulas.index')->with('status','Aula creada correctamente.');
    }

    public function edit(Aula $aula)
    {
        $tipos = $this->tipos;
        return view('aulas.edit', compact('aula','tipos'));
    }

    public function update(Request $request, Aula $aula)
    {
        $data = $request->validate([
            'codigo' => ['required','string','max:50','unique:aulas,codigo,'.$aula->id_aula.',id_aula'],
            'nombre' => ['required','string','max:120'],
            'tipo' => ['required','string','in:TEORIA,LABORATORIO,VIRTUAL,AUDITORIO'],
            'capacidad' => ['required','integer','min:1'],
            'ubicacion' => ['nullable','string'],
        ]);

        $aula->update($data);
        return redirect()->route('aulas.index')->with('status','Aula actualizada correctamente.');
    }

    public function destroy(Aula $aula)
    {
        if (Horario::where('id_aula', $aula->id_aula)->exists()) {
            return back()->withErrors(['general' => 'No se puede eliminar: el aula está en uso en horarios.']);
        }
        $aula->delete();
        return redirect()->route('aulas.index')->with('status','Aula eliminada.');
    }

    public function disponibilidad(Request $request)
    {
        $dia = $request->get('dia');
        $horaInicio = $request->get('hora_inicio');
        $horaFin = $request->get('hora_fin');

        $aulas = Aula::orderBy('codigo')->get();
        $ocupadas = collect();

        if ($dia && $horaInicio && $horaFin) {
            // Normaliza día: admite número (1..7) o nombre ('Lunes', ...)
            $mapDias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
            $diaNorm = array_key_exists((int)$dia, $mapDias) ? $mapDias[(int)$dia] : $dia;
            // Corrige posibles problemas de codificación de acentos
            if ($diaNorm === 'Mi�rcoles') { $diaNorm = 'Miércoles'; }
            if ($diaNorm === 'S�bado') { $diaNorm = 'Sábado'; }
            // Evita ambigüedad de tipos con OVERLAPS usando comparación directa
            // Overlap si: inicio < fin_param AND fin > inicio_param
            $ocupadas = DB::table('horarios')
                ->select('id_aula')
                ->whereNotNull('id_aula')
                ->where('dia', $diaNorm)
                ->whereRaw('hora_inicio < ?::time AND hora_fin > ?::time', [$horaFin, $horaInicio])
                ->pluck('id_aula');
        }

        $disponibles = $aulas->whereNotIn('id_aula', $ocupadas->filter());

        return view('aulas.disponibilidad', compact('dia','horaInicio','horaFin','aulas','ocupadas','disponibles'));
    }
}
