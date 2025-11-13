<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsBitacora;

class Facultad extends Model
{
    use HasFactory, LogsBitacora;

    protected $table = 'facultades';
    protected $primaryKey = 'id_facultad';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'sigla',
        'descripcion',
    ];
}
