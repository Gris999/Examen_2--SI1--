<?php

namespace App\Http\Controllers\Attendance;
use App\Http\Controllers\Controller;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Grupo;
use App\Models\DocenteMateriaGestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\QueryException;

class AsistenciaController extends Controller
{
    public function __construct()
    {
        // Accesos por rol
        // - index/create/store/qr/qrRegister: docente, coordinador, decano, admin
        // - edit/update/destroy: solo admin o coordinador
        $this->middleware('role:administrador,admin,coordinador,decano,docente')->only(['index','create','store','qr','qrRegister']);
        $this->middleware('role:administrador,admin,coordinador')->only(['edit','update','destroy']);
    }
    public function index(Request $request)
    {
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $docenteId = $request->integer('docente_id');
        $estado = $request->get('estado');

        $q = Asistencia::with(['docente.usuario','horario.grupo.materia','horario.grupo.gestion','horario.aula'])
            ->when($desde, fn($x)=>$x->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($x)=>$x->whereDate('fecha', '<=', $hasta))
            ->when($docenteId, fn($x)=>$x->where('id_docente', $docenteId))
            ->when($estado, fn($x)=>$x->where('estado', $estado))
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'desc');

        $docente = $this->docenteActual();
        if ($docente) {
            $q->where('id_docente', $docente->id_docente);
        }

        $asistencias = $q->paginate(12)->withQueryString();
        $docentes = Docente::with('usuario')->orderBy('id_docente','desc')->get();
        $horariosHoy = $this->horariosDocenteParaHoy($docente);
        return view('asistencias.index', compact('asistencias','docentes','desde','hasta','docenteId','estado','horariosHoy'));
    }

    public function create(Request $request)
    {
        $fecha = $request->date('fecha') ?: \Carbon\Carbon::now('America/La_Paz')->toDateString();
        $iso = \Carbon\Carbon::parse($fecha, 'America/La_Paz')->dayOfWeekIso;
        $map = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
        $dow = $map[$iso] ?? 'Lunes';
        $docente = $this->docenteActual();

        $horarios = Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->where('dia', $dow)
            ->when($docente ?? false, function($q) use ($docente) {
                $ids = DocenteMateriaGestion::where('id_docente', $docente->id_docente)->pluck('id_docente_materia_gestion');
                if ($ids->isNotEmpty()) {
                    $q->whereIn('id_docente_materia_gestion', $ids);
                }
            })
            ->orderBy('id_horario','desc')
            ->get();

        $horariosHoy = $this->horariosDocenteParaHoy($docente);
        return view('asistencias.create', compact('horarios','fecha','horariosHoy'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_horario' => ['required','integer','exists:horarios,id_horario'],
            'fecha' => ['required','date'],
            'metodo' => ['nullable','in:FORM,MANUAL,QR'],
            'justificacion' => ['nullable','string'],
        ]);

        $horario = Horario::with('docenteMateriaGestion')->findOrFail($data['id_horario']);
        $docenteId = $horario->docenteMateriaGestion->id_docente ?? null;
        if (!$docenteId) {
            return back()->withErrors(['id_horario' => 'El horario no tiene docente asignado.'])->withInput();
        }

        // Si el usuario es DOCENTE, solo puede registrar para sus propios horarios
        if ($docente = $this->docenteActual()) {
            if ((int)$docente->id_docente !== (int)$docenteId) {
                return back()->withErrors(['id_horario' => 'No puede registrar asistencia para un horario que no le pertenece.'])->withInput();
            }
        }

        $now = \Carbon\Carbon::now('America/La_Paz');
        $estado = $now->format('H:i') > $horario->hora_inicio ? 'RETRASO' : 'PRESENTE';

        $existsKey = [
            'id_horario' => $horario->id_horario,
            'id_docente' => $docenteId,
            'fecha' => $data['fecha'],
        ];

        $payload = [
            'hora_entrada' => $now->format('H:i:s'),
            'metodo' => $data['metodo'] ?? 'FORM',
            'estado' => $estado,
            'justificacion' => $data['justificacion'] ?? null,
            'registrado_por' => auth()->user()->id_usuario ?? null,
            'fecha_registro' => $now,
        ];

        try {
            // Evitar duplicados por constraint única (id_horario, fecha, id_docente)
            if (Asistencia::where($existsKey)->exists()) {
                return redirect()->route('asistencias.index')->with('warning','Ya existía un registro para ese docente/fecha/horario. Se mantuvo el existente.');
            }
            Asistencia::create($existsKey + $payload);
        } catch (QueryException $e) {
            if ((string)$e->getCode() === '23505') {
                return redirect()->route('asistencias.index')->with('warning','Registro duplicado detectado. Se mantuvo el existente.');
            }
            throw $e;
        }

        return redirect()->route('asistencias.index')->with('status','Asistencia registrada.');
    }

    public function edit(Asistencia $asistencia)
    {
        return view('asistencias.edit', compact('asistencia'));
    }

    public function update(Request $request, Asistencia $asistencia)
    {
        $data = $request->validate([
            'estado' => ['required','in:PRESENTE,AUSENTE,RETRASO,JUSTIFICADO'],
            'justificacion' => ['nullable','string'],
        ]);
        $asistencia->update($data);
        return redirect()->route('asistencias.index')->with('status','Asistencia actualizada.');
    }

    public function destroy(Asistencia $asistencia)
    {
        $asistencia->delete();
        return redirect()->route('asistencias.index')->with('status','Asistencia eliminada.');
    }

    // QR: muestra un QR que codifica una URL firmada para registrar asistencia
    public function qr(Horario $horario)
    {
        $fecha = \Carbon\Carbon::now('America/La_Paz')->toDateString();
        $signed = URL::temporarySignedRoute('asistencias.qr.register', \Carbon\Carbon::now('America/La_Paz')->addMinutes(15), [
            'horario' => $horario->id_horario,
            'fecha' => $fecha,
        ]);
        return view('asistencias.qr', [
            'horario' => $horario->load(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula']),
            'fecha' => $fecha,
            'signed' => $signed,
        ]);
    }

    public function qrRegister(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Link de QR inválido o expirado');
        }
        $horario = Horario::with('docenteMateriaGestion')->findOrFail($request->query('horario'));
        $fecha = $request->query('fecha');
        $docenteId = $horario->docenteMateriaGestion->id_docente ?? null;
        if (!$docenteId) abort(400, 'Horario sin docente');

        $now = \Carbon\Carbon::now('America/La_Paz');
        $estado = $now->format('H:i') > $horario->hora_inicio ? 'RETRASO' : 'PRESENTE';

        $existsKey = [
            'id_horario' => $horario->id_horario,
            'id_docente' => $docenteId,
            'fecha' => $fecha,
        ];
        $payload = [
            'hora_entrada' => $now->format('H:i:s'),
            'metodo' => 'QR',
            'estado' => $estado,
            'registrado_por' => auth()->user()->id_usuario ?? null,
            'fecha_registro' => $now,
        ];

        try {
            if (Asistencia::where($existsKey)->exists()) {
                return redirect()->route('asistencias.index')->with('warning','Ya existía un registro para ese docente/fecha/horario (QR). Se mantuvo el existente.');
            }
            Asistencia::create($existsKey + $payload);
        } catch (QueryException $e) {
            if ((string)$e->getCode() === '23505') {
                return redirect()->route('asistencias.index')->with('warning','Registro duplicado (QR). Se mantuvo el existente.');
            }
            throw $e;
        }

        return redirect()->route('asistencias.index')->with('status','Asistencia por QR registrada.');
    }

    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if (!$user) { return null; }
        $roles = $user->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
        if (!in_array('docente', $roles, true)) {
            return null;
        }
        return Docente::where('id_usuario', $user->id_usuario ?? 0)->first();
    }

    private function horariosDocenteParaHoy(?Docente $docente)
    {
        if (!$docente) { return collect(); }
        $dow = $this->dowName(\Carbon\Carbon::now('America/La_Paz')->dayOfWeekIso);
        $ids = DocenteMateriaGestion::where('id_docente', $docente->id_docente)->pluck('id_docente_materia_gestion');
        if ($ids->isEmpty()) { return collect(); }

        return Horario::with(['grupo.materia','grupo.gestion','docenteMateriaGestion.docente.usuario','aula'])
            ->where('dia', $dow)
            ->whereIn('id_docente_materia_gestion', $ids)
            ->orderBy('hora_inicio','asc')
            ->get();
    }

    private function dowName(int $iso): string
    {
        // 1=Lunes ... 7=Domingo; nuestra BD usa Lunes..Sábado
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







