<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsBitacora;

class Aula extends Model
{
    use HasFactory, LogsBitacora;

    protected $table = 'aulas';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'capacidad',
        'ubicacion',
    ];

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'id_aula', 'id_aula');
    }
}
