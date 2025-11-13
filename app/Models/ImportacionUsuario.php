<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionUsuario extends Model
{
    protected $table = 'importaciones_usuarios';
    protected $primaryKey = 'id_importacion';
    public $timestamps = false;

    protected $fillable = [
        'archivo_nombre',
        'total_filas',
        'filas_procesadas',
        'fecha',
        'usuario_ejecutor',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total_filas' => 'integer',
        'filas_procesadas' => 'integer',
    ];
}

