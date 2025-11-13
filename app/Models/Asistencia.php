<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsBitacora;

class Asistencia extends Model
{
    use HasFactory, LogsBitacora;

    protected $table = 'asistencias';
    protected $primaryKey = 'id_asistencia';
    public $timestamps = false;

    protected $fillable = [
        'id_horario',
        'id_docente',
        'fecha',
        'hora_entrada',
        'metodo',
        'estado',
        'justificacion',
        'registrado_por',
        'fecha_registro',
    ];

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'id_horario', 'id_horario');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function registradoPor()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por', 'id_usuario');
    }
}
