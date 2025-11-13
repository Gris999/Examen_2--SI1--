<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsBitacora;

class Horario extends Model
{
    use LogsBitacora;
    protected $table = 'horarios';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'id_docente_materia_gestion',
        'id_grupo',
        'id_aula',
        'dia',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'virtual_plataforma',
        'virtual_enlace',
        'observacion',
        'estado',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function docenteMateriaGestion()
    {
        return $this->belongsTo(DocenteMateriaGestion::class, 'id_docente_materia_gestion', 'id_docente_materia_gestion');
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }
}

