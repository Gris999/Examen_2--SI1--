<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\CargaHorariaController;
use App\Http\Controllers\AprobacionController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ConsultaHorarioController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\HistorialAsistenciaController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\BitacoraController;
use Illuminate\Support\Facades\App;

// Autenticación
Route::middleware('web')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::view('/login/select', 'auth.select-profile')->name('login.select');
    Route::view('/login/usuario', 'auth.select-usuario-rol')->name('login.select.usuario');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Recuperación de contraseña (modo desarrollo: muestra link en pantalla)
    Route::get('/password/forgot', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::view('/password/sent', 'auth.passwords.sent')->name('password.sent');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');

    // Dashboard protegido
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth_simple','audit'])->name('dashboard');

    // CU2: Gestionar Docentes
    Route::middleware(['auth_simple','audit'])->group(function () {
        Route::resource('docentes', DocenteController::class)->except(['show']);
        Route::get('docentes/{docente}/carga', [DocenteController::class, 'showCarga'])->name('docentes.carga');
        Route::post('docentes/{docente}/toggle', [DocenteController::class, 'toggle'])->name('docentes.toggle');
        Route::resource('materias', MateriaController::class)->except(['show']);
        Route::resource('grupos', GrupoController::class)->except(['show']);
        Route::get('grupos/{grupo}/docentes', [GrupoController::class, 'docentes'])->name('grupos.docentes');
        Route::post('grupos/{grupo}/docentes', [GrupoController::class, 'addDocente'])->name('grupos.docentes.add');
        Route::delete('grupos/{grupo}/docentes/{dmg}', [GrupoController::class, 'removeDocente'])->name('grupos.docentes.remove');

        // CU5: Gestionar Aulas
        Route::resource('aulas', AulaController::class)->except(['show']);
        Route::get('aulas/disponibilidad', [AulaController::class, 'disponibilidad'])->name('aulas.disponibilidad');

        // CU6: Asignar Carga Horaria (usa tabla 'horarios')
        Route::resource('carga', CargaHorariaController::class)->parameters(['carga' => 'cargum'])->except(['show']);

        // CU7: Aprobar / Rechazar Asignaciones
        Route::middleware('role:administrador,admin,decano')->group(function(){
            Route::get('aprobaciones', [AprobacionController::class, 'index'])->name('aprobaciones.index');
            Route::post('aprobaciones/{dmg}/aprobar', [AprobacionController::class, 'approve'])->name('aprobaciones.approve');
            Route::post('aprobaciones/{dmg}/rechazar', [AprobacionController::class, 'reject'])->name('aprobaciones.reject');
            Route::get('aprobaciones/historial', [AprobacionController::class, 'historial'])->name('aprobaciones.historial');
        });

        // CU8: Gestionar Horarios (manual básico)
        Route::resource('horarios', HorarioController::class)->except(['show']);
        Route::get('horarios/disponibilidad', [HorarioController::class, 'getDisponibilidad'])->name('horarios.disponibilidad');
        Route::post('horarios/generar', [HorarioController::class, 'generateAutomatic'])
            ->middleware('role:administrador,admin,coordinador')
            ->name('horarios.generar');

        // CU10: Registrar Asistencia Docente
        Route::resource('asistencias', AsistenciaController::class)->except(['show']);
        Route::get('asistencias/qr/{horario}', [AsistenciaController::class, 'qr'])->name('asistencias.qr');
        Route::get('asistencias/qr-register', [AsistenciaController::class, 'qrRegister'])
            ->name('asistencias.qr.register'); // ruta firmada

        // CU9: Consultar Horarios (Docente / Aula / Grupo / Semana)
        Route::get('consultas/horarios', [ConsultaHorarioController::class, 'index'])->name('consultas.horarios.index');
        Route::get('consultas/horarios/export', [ConsultaHorarioController::class, 'export'])->name('consultas.horarios.export');

        // CU6: Asignaciones (Docente-Materia-Gestión)
        Route::resource('asignaciones', AsignacionController::class)->only(['index','create','store','edit','update','destroy']);

        // CU11: Historial de Asistencia
        Route::get('historial', [HistorialAsistenciaController::class, 'index'])->name('historial.index');
        Route::get('historial/export', [HistorialAsistenciaController::class, 'exportCsv'])->name('historial.export');
        Route::get('historial/print', [HistorialAsistenciaController::class, 'print'])->name('historial.print');
        Route::get('historial/pdf', [HistorialAsistenciaController::class, 'exportPdf'])->name('historial.pdf');

        // CU12: Reportes y Analítica (solo ADMIN/DIRECTOR, validado en el controlador)
        Route::get('reportes', [ReportesController::class, 'index'])->name('reportes.index');
        Route::get('reportes/export', [ReportesController::class, 'export'])->name('reportes.export');
        Route::get('reportes/print', [ReportesController::class, 'print'])->name('reportes.print');

        // CU13: Importar Datos Masivos
        Route::get('importaciones', [\App\Http\Controllers\ImportacionController::class, 'index'])->name('importaciones.index');
        Route::get('importaciones/create', [\App\Http\Controllers\ImportacionController::class, 'create'])->name('importaciones.create');
        Route::post('importaciones', [\App\Http\Controllers\ImportacionController::class, 'store'])->name('importaciones.store');
        Route::get('importaciones/template/{tipo}.xlsx', [\App\Http\Controllers\ImportacionController::class, 'templateXlsx'])->name('importaciones.template.xlsx');

        // CU14: Gestionar Usuarios y Roles (solo ADMIN/DIRECTOR)
        Route::middleware('role:administrador,admin,director,director de carrera')->group(function(){
            Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios'=>'usuario'])->except(['show']);
            Route::resource('roles', RolController::class)->except(['show']);
        });

        // CU15: Consultar Bitácora (solo ADMIN o DECANO)
        Route::middleware('role:administrador,admin,decano')->group(function(){
            Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
            Route::get('bitacora/export', [BitacoraController::class, 'exportCsv'])->name('bitacora.export');
            Route::get('bitacora/pdf', [BitacoraController::class, 'exportPdf'])->name('bitacora.pdf');
            Route::get('bitacora/print', [BitacoraController::class, 'print'])->name('bitacora.print');
        });
    });
});

// Ruta de siembra rápida para desarrollo local (crea un usuario demo)
if (App::environment('local')) {
    Route::get('/dev/seed-user', function () {
        $usuarioModel = \App\Models\Usuario::class;
        $exists = $usuarioModel::where('correo', 'admin@example.com')->exists();
        if (!$exists) {
            $usuarioModel::create([
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'correo' => 'admin@example.com',
                'contrasena' => 'secret123',
                'telefono' => '70000000',
                'activo' => true,
            ]);
        }
        return 'Usuario de desarrollo listo (usuarios): admin@example.com / secret123';
    });
}
