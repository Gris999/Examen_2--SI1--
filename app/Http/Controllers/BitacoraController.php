<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    private function ensureAuthorized(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('decano',$roles);
        abort_unless($ok, 403);
    }

    public function index(Request $request)
    {
        $this->ensureAuthorized();

        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $usuarioId = $request->integer('usuario_id');
        $accion = $request->get('accion');
        $tabla = $request->get('tabla');
        $ip = $request->get('ip');

        $q = DB::table('bitacora as b')
            ->leftJoin('usuarios as u','u.id_usuario','=','b.id_usuario')
            ->select('b.*', DB::raw("COALESCE(u.nombre||' '||u.apellido,u.correo) as usuario"))
            ->when($desde, fn($x)=>$x->whereDate('b.fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('b.fecha','<=',$hasta))
            ->when($usuarioId, fn($x)=>$x->where('b.id_usuario',$usuarioId))
            ->when($accion, fn($x)=>$x->where('b.accion',$accion))
            ->when($tabla, fn($x)=>$x->where('b.tabla_afectada',$tabla))
            ->when($ip, fn($x)=>$x->where('b.ip_origen',$ip))
            ->orderBy('b.fecha','desc')->orderBy('b.id_bitacora','desc');

        $rows = $q->paginate(20)->withQueryString();

        $usuarios = DB::table('usuarios')->orderBy('nombre')->get();
        $acciones = DB::table('bitacora')->select('accion')->distinct()->orderBy('accion')->pluck('accion');
        $tablas = DB::table('bitacora')->select('tabla_afectada')->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada');

        return view('bitacora.index', compact('rows','usuarios','acciones','tablas','desde','hasta','usuarioId','accion','tabla','ip'));
    }

    public function exportCsv(Request $request)
    {
        $this->ensureAuthorized();
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $usuarioId = $request->integer('usuario_id');
        $accion = $request->get('accion');
        $tabla = $request->get('tabla');
        $ip = $request->get('ip');

        $rows = DB::table('bitacora as b')
            ->leftJoin('usuarios as u','u.id_usuario','=','b.id_usuario')
            ->select('b.*', DB::raw("COALESCE(u.nombre||' '||u.apellido,u.correo) as usuario"))
            ->when($desde, fn($x)=>$x->whereDate('b.fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('b.fecha','<=',$hasta))
            ->when($usuarioId, fn($x)=>$x->where('b.id_usuario',$usuarioId))
            ->when($accion, fn($x)=>$x->where('b.accion',$accion))
            ->when($tabla, fn($x)=>$x->where('b.tabla_afectada',$tabla))
            ->when($ip, fn($x)=>$x->where('b.ip_origen',$ip))
            ->orderBy('b.fecha','desc')->orderBy('b.id_bitacora','desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bitacora.csv"',
        ];

        return response()->stream(function() use ($rows){
            $out = fopen('php://output','w');
            fputcsv($out, ['Fecha','Usuario','Accion','Tabla','Id Afectado','IP','Descripcion']);
            foreach($rows as $r){
                fputcsv($out,[
                    $r->fecha,
                    $r->usuario,
                    $r->accion,
                    $r->tabla_afectada,
                    $r->id_afectado,
                    $r->ip_origen,
                    $r->descripcion,
                ]);
            }
            fclose($out);
        },200,$headers);
    }

    public function exportPdf(Request $request)
    {
        $this->ensureAuthorized();
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(501, 'Dompdf no instalado en el servidor');
        }
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $usuarioId = $request->integer('usuario_id');
        $accion = $request->get('accion');
        $tabla = $request->get('tabla');
        $ip = $request->get('ip');

        $rows = DB::table('bitacora as b')
            ->leftJoin('usuarios as u','u.id_usuario','=','b.id_usuario')
            ->select('b.*', DB::raw("COALESCE(u.nombre||' '||u.apellido,u.correo) as usuario"))
            ->when($desde, fn($x)=>$x->whereDate('b.fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('b.fecha','<=',$hasta))
            ->when($usuarioId, fn($x)=>$x->where('b.id_usuario',$usuarioId))
            ->when($accion, fn($x)=>$x->where('b.accion',$accion))
            ->when($tabla, fn($x)=>$x->where('b.tabla_afectada',$tabla))
            ->when($ip, fn($x)=>$x->where('b.ip_origen',$ip))
            ->orderBy('b.fecha','desc')->orderBy('b.id_bitacora','desc')
            ->get();

        $html = view('bitacora.print', compact('rows','desde','hasta','usuarioId','accion','tabla','ip'))->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bitacora.pdf"'
        ]);
    }

    public function print(Request $request)
    {
        $this->ensureAuthorized();
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $usuarioId = $request->integer('usuario_id');
        $accion = $request->get('accion');
        $tabla = $request->get('tabla');
        $ip = $request->get('ip');

        $rows = DB::table('bitacora as b')
            ->leftJoin('usuarios as u','u.id_usuario','=','b.id_usuario')
            ->select('b.*', DB::raw("COALESCE(u.nombre||' '||u.apellido,u.correo) as usuario"))
            ->when($desde, fn($x)=>$x->whereDate('b.fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('b.fecha','<=',$hasta))
            ->when($usuarioId, fn($x)=>$x->where('b.id_usuario',$usuarioId))
            ->when($accion, fn($x)=>$x->where('b.accion',$accion))
            ->when($tabla, fn($x)=>$x->where('b.tabla_afectada',$tabla))
            ->when($ip, fn($x)=>$x->where('b.ip_origen',$ip))
            ->orderBy('b.fecha','desc')->orderBy('b.id_bitacora','desc')
            ->get();

        return view('bitacora.print', compact('rows','desde','hasta','usuarioId','accion','tabla','ip'));
    }

    // XLS export en HTML estilado
    public function exportXlsx(Request $request)
    {
        $this->ensureAuthorized();
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $usuarioId = $request->integer('usuario_id');
        $accion = $request->get('accion');
        $tabla = $request->get('tabla');
        $ip = $request->get('ip');

        $rows = DB::table('bitacora as b')
            ->leftJoin('usuarios as u','u.id_usuario','=','b.id_usuario')
            ->select('b.*', DB::raw("COALESCE(u.nombre||' '||u.apellido,u.correo) as usuario"))
            ->when($desde, fn($x)=>$x->whereDate('b.fecha','>=',$desde))
            ->when($hasta, fn($x)=>$x->whereDate('b.fecha','<=',$hasta))
            ->when($usuarioId, fn($x)=>$x->where('b.id_usuario',$usuarioId))
            ->when($accion, fn($x)=>$x->where('b.accion',$accion))
            ->when($tabla, fn($x)=>$x->where('b.tabla_afectada',$tabla))
            ->when($ip, fn($x)=>$x->where('b.ip_origen',$ip))
            ->orderBy('b.fecha','desc')->orderBy('b.id_bitacora','desc')
            ->get();

        $total = $rows->count();
        $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
            .'<title>Bitácora</title>'
            .'<style>body{font-family:Segoe UI,Arial,sans-serif;font-size:12px}h3{margin:0 0 6px 0}.small{color:#555;margin:0 0 12px 0}table{border-collapse:collapse;width:100%}th,td{border:1px solid #999;padding:6px}th{background:#f1f5f9;font-weight:700}tbody tr:nth-child(even){background:#fafafa}.nowrap{white-space:nowrap}</style></head><body>'
            .'<h3>Bitácora del Sistema</h3>'
            .'<p class="small">Total: '.e((string)$total).' · Rango: '.e($desde ?? '—').' a '.e($hasta ?? '—').'</p>'
            .'<table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>ID</th><th>IP</th><th>Descripción</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr>'
                .'<td class="nowrap">'.e((string)$r->fecha).'</td>'
                .'<td>'.e((string)($r->usuario ?? '-')).'</td>'
                .'<td>'.e((string)$r->accion).'</td>'
                .'<td>'.e((string)$r->tabla_afectada).'</td>'
                .'<td>'.e((string)($r->id_afectado ?? '')).'</td>'
                .'<td>'.e((string)($r->ip_origen ?? '')).'</td>'
                .'<td>'.e((string)($r->descripcion ?? '')).'</td>'
                .'</tr>';
        }
        $html .= '</tbody></table></body></html>';

        return response("\xEF\xBB\xBF".$html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bitacora.xls"',
        ]);
    }

    // Detalle de registro
    public function show(int $id)
    {
        $this->ensureAuthorized();
        $row = DB::table('bitacora as b')
            ->leftJoin('usuarios as u','u.id_usuario','=','b.id_usuario')
            ->select('b.*', DB::raw("COALESCE(u.nombre||' '||u.apellido,u.correo) as usuario"))
            ->where('b.id_bitacora', $id)
            ->firstOrFail();
        return view('bitacora.show', compact('row'));
    }
}
