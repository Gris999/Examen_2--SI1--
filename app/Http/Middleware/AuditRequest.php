<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditRequest
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $route = $request->route();
            $routeName = $route?->getName();
            $method = strtoupper($request->method());
            $uri = '/'.ltrim($request->path(), '/');

            // Determinar acción
            $accion = match ($method) {
                'POST' => 'CREATE',
                'PUT', 'PATCH' => 'UPDATE',
                'DELETE' => 'DELETE',
                default => 'VIEW',
            };
            if ($routeName && (str_contains($routeName,'export') || str_contains($routeName,'csv'))) {
                $accion = 'EXPORT';
            } elseif ($routeName && (str_contains($routeName,'pdf') || str_contains($routeName,'print'))) {
                $accion = 'PRINT';
            }

            // Tabla afectada aproximada = primer segmento del path
            $firstSeg = explode('/', trim($request->path(), '/'))[0] ?? null;
            $tabla = $firstSeg ?: ($routeName ? explode('.', $routeName)[0] : null);

            // Id afectado: último parámetro numérico si existe
            $idAfectado = null;
            $params = $route?->parameters() ?? [];
            if (!empty($params)) {
                foreach (array_reverse($params) as $val) {
                    if (is_scalar($val)) { $v = (string)$val; }
                    elseif (is_object($val) && method_exists($val,'getKey')) { $v = (string)$val->getKey(); }
                    else { $v = null; }
                    if ($v !== null) { $idAfectado = $v; break; }
                }
            }

            DB::table('bitacora')->insert([
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'accion' => $accion,
                'tabla_afectada' => $tabla,
                'id_afectado' => (string)($idAfectado ?? ''),
                'ip_origen' => $request->ip(),
                'descripcion' => trim(($routeName ? "route=$routeName; " : '').$method.' '.$uri),
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silencioso: no debe romper la respuesta
        }

        return $response;
    }
}

