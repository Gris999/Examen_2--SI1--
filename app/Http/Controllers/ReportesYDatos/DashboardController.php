<?php

namespace App\Http\Controllers\ReportesYDatos;
use App\Http\Controllers\Controller;

use App\Models\Docente;
use App\Models\Materia;
use App\Models\Grupo;
use App\Models\Aula;
use App\Models\DocenteMateriaGestion;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = [
            'docentes' => Docente::count(),
            'materias' => Materia::count(),
            'grupos' => Grupo::count(),
            'aulas' => Aula::count(),
            'pendientes' => DocenteMateriaGestion::where('estado','PENDIENTE')->count(),
        ];

        return view('dashboard', compact('counts'));
    }
}

