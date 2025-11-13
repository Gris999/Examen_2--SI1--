<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsBitacora;

class Carrera extends Model
{
    use HasFactory, LogsBitacora;

    protected $table = 'carreras';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = [
        'id_facultad',
        'nombre',
        'sigla',
    ];
}
