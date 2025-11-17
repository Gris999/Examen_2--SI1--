<?php

namespace App\Http\Controllers\AsignacionYHorarios;
use App\Http\Controllers\Controller;

use App\Models\DocenteMateriaGestion as DMG;
use App\Models\Gestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AprobacionController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->get('estado', 'PENDIENTE');
        $gestion = $request->integer('gestion_id');

        $asignaciones = DMG::with(['docente.usuario','materia','gestion'])
            ->when($estado, fn($q)=>$q->where('estado', strtoupper($estado)))
            ->when($gestion, fn($q)=>$q->where('id_gestion', $gestion))
            ->withCount('horarios')
            ->orderBy('id_docente_materia_gestion','desc')
            ->paginate(10)
            ->withQueryString();

        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();

        return view('aprobaciones.index', [
            'asignaciones' => $asignaciones,
            'estado' => $estado,
            'gestion' => $gestion,
            'gestiones' => $gestiones,
        ]);
    }

    public function approve(DMG $dmg)
    {
        if (! $this->userCanApprove()) { abort(403); }
        DB::transaction(function () use ($dmg) {
            $dmg->estado = 'APROBADA';
            $dmg->aprobado_por = auth()->user()->id_usuario ?? auth()->id();
            $dmg->aprobado_en = now();
            $dmg->save();
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => 'APROBAR',
                'tabla_afectada' => 'docente_materia_gestion',
                'id_afectado' => (string)$dmg->id_docente_materia_gestion,
                'ip_origen' => request()->ip(),
                'descripcion' => 'Asignación aprobada',
                'fecha' => now(),
            ]);
        });
        return back()->with('status', 'Asignación aprobada.');
    }

    public function reject(DMG $dmg)
    {
        if (! $this->userCanApprove()) { abort(403); }
        DB::transaction(function () use ($dmg) {
            $dmg->estado = 'RECHAZADA';
            $dmg->aprobado_por = auth()->user()->id_usuario ?? auth()->id();
            $dmg->aprobado_en = now();
            $dmg->save();
            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => 'RECHAZAR',
                'tabla_afectada' => 'docente_materia_gestion',
                'id_afectado' => (string)$dmg->id_docente_materia_gestion,
                'ip_origen' => request()->ip(),
                'descripcion' => 'Asignación rechazada',
                'fecha' => now(),
            ]);
        });
        return back()->with('status', 'Asignación rechazada.');
    }

    private function userCanApprove(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        $names = $u->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
        return in_array('administrador', $names) || in_array('admin', $names) || in_array('decano', $names);
    }

    public function historial(Request $request)
    {
        $estado = $request->get('estado');
        $docente = $request->integer('docente_id');
        $gestion = $request->integer('gestion_id');

        $asignaciones = DMG::with(['docente.usuario','materia','gestion'])
            ->when($estado, fn($q)=>$q->where('estado', strtoupper($estado)))
            ->when($docente, fn($q)=>$q->where('id_docente', $docente))
            ->when($gestion, fn($q)=>$q->where('id_gestion', $gestion))
            ->orderBy('aprobado_en','desc')
            ->orderBy('id_docente_materia_gestion','desc')
            ->paginate(12)
            ->withQueryString();

        $gestiones = Gestion::orderBy('fecha_inicio','desc')->get();

        return view('aprobaciones.historial', [
            'asignaciones' => $asignaciones,
            'estado' => $estado,
            'docente' => $docente,
            'gestion' => $gestion,
            'gestiones' => $gestiones,
        ]);
    }
}

