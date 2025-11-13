<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait LogsBitacora
{
    protected static function bootLogsBitacora(): void
    {
        // Crear
        static::created(function ($model) {
            self::writeBitacora($model, 'INSERT', 'Registro creado');
        });

        // Actualizar
        static::updated(function ($model) {
            $changed = array_keys($model->getChanges() ?? []);
            $changed = array_values(array_filter($changed, fn($k)=>!in_array($k, ['updated_at'])));
            $desc = 'Registro actualizado'.(count($changed) ? ' (campos: '.implode(',', $changed).')' : '');
            self::writeBitacora($model, 'UPDATE', $desc);
        });

        // Eliminar
        static::deleted(function ($model) {
            self::writeBitacora($model, 'DELETE', 'Registro eliminado');
        });
    }

    protected static function writeBitacora($model, string $accion, string $descripcion): void
    {
        try {
            // No registrar cambios de la propia bitácora
            if (method_exists($model, 'getTable') && $model->getTable() === 'bitacora') {
                return;
            }

            $tabla = method_exists($model, 'getTable') ? $model->getTable() : null;
            $id = method_exists($model, 'getKey') ? (string)($model->getKey() ?? '') : '';
            $user = auth()->user();
            $userId = $user->id_usuario ?? (method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : null);
            $ip = request()?->ip() ?? 'system';

            DB::table('bitacora')->insert([
                'id_usuario' => $userId,
                'accion' => $accion,
                'tabla_afectada' => $tabla,
                'id_afectado' => $id,
                'ip_origen' => $ip,
                'descripcion' => $descripcion,
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silencioso: no afectar el flujo de negocio por fallo de auditoría
        }
    }
}

