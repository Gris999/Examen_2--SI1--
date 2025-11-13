<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsBitacora;

class DocenteMateriaGestion extends Model
{
    use LogsBitacora;
    use HasFactory;

    protected $table = 'docente_materia_gestion';
    protected $primaryKey = 'id_docente_materia_gestion';
    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_materia',
        'id_gestion',
        'fecha_asignacion',
        'estado',
        'aprobado_por',
        'aprobado_en',
        'activo',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'id_gestion', 'id_gestion');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'id_docente_materia_gestion', 'id_docente_materia_gestion');
    }
}
