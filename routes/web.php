<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutenticacionYSeguridad\AuthController;
use App\Http\Controllers\AutenticacionYSeguridad\PasswordResetController;
use App\Http\Controllers\GestionAcademica\DocenteController;
use App\Http\Controllers\GestionAcademica\MateriaController;
use App\Http\Controllers\GestionAcademica\GrupoController;
use App\Http\Controllers\GestionAcademica\AulaController;
use App\Http\Controllers\AsignacionYHorarios\CargaHorariaController;
use App\Http\Controllers\AsignacionYHorarios\AprobacionController;
use App\Http\Controllers\AsignacionYHorarios\HorarioController;
use App\Http\Controllers\ControlDeAsistencia\AsistenciaController;
use App\Http\Controllers\AsignacionYHorarios\ConsultaHorarioController;
use App\Http\Controllers\AsignacionYHorarios\AsignacionController;
use App\Http\Controllers\AsignacionYHorarios\ImportacionController;
use App\Http\Controllers\ControlDeAsistencia\HistorialAsistenciaController;
use App\Http\Controllers\ReportesYDatos\ReportesController;
use App\Http\Controllers\AutenticacionYSeguridad\UsuarioController;
use App\Http\Controllers\AutenticacionYSeguridad\RolController;
use App\Http\Controllers\ReportesYDatos\BitacoraController;
use App\Http\Controllers\ControlDeAsistencia\DocentePortalController;
use App\Http\Controllers\ReportesYDatos\DashboardController;
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
    // Home: si no está autenticado, mostrar selector de perfil; si está autenticado, ir al dashboard
    Route::get('/', function(){
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login.select');
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth_simple','audit'])->name('dashboard');

    // CU2: Gestionar Docentes
    Route::middleware(['auth_simple','audit'])->group(function () {
        Route::resource('docentes', DocenteController::class)->except(['show'])->middleware('role:administrador,admin,coordinador');
        Route::get('docentes/{docente}/carga', [DocenteController::class, 'showCarga'])->name('docentes.carga');
        Route::post('docentes/{docente}/toggle', [DocenteController::class, 'toggle'])->name('docentes.toggle');
        Route::resource('materias', MateriaController::class)->except(['show'])->middleware('role:administrador,admin,coordinador');
        Route::resource('grupos', GrupoController::class)->except(['show'])->middleware('role:administrador,admin,coordinador');
        Route::get('grupos/{grupo}/docentes', [GrupoController::class, 'docentes'])->name('grupos.docentes');
        Route::post('grupos/{grupo}/docentes', [GrupoController::class, 'addDocente'])->name('grupos.docentes.add');
        Route::delete('grupos/{grupo}/docentes/{dmg}', [GrupoController::class, 'removeDocente'])->name('grupos.docentes.remove');

        // CU5: Gestionar Aulas
        Route::resource('aulas', AulaController::class)->except(['show'])->middleware('role:administrador,admin,coordinador');

        // Decano: rutas de solo lectura (index) para catálogos
        Route::middleware('role:decano')->group(function(){
            Route::get('consulta/docentes', [DocenteController::class, 'index'])->name('consulta.docentes');
            Route::get('consulta/materias', [MateriaController::class, 'index'])->name('consulta.materias');
            Route::get('consulta/grupos', [GrupoController::class, 'index'])->name('consulta.grupos');
            Route::get('consulta/aulas', [AulaController::class, 'index'])->name('consulta.aulas');
        });

        Route::middleware('role:docente')->group(function () {
            Route::get('docente/portal', [DocentePortalController::class, 'index'])->name('docente.portal');
            Route::get('docente/portal/export', [DocentePortalController::class, 'exportHorarios'])->name('docente.portal.export');
        });
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
        Route::get('historial/xlsx', [HistorialAsistenciaController::class, 'exportXlsx'])->name('historial.xlsx');
        Route::get('historial/print', [HistorialAsistenciaController::class, 'print'])->name('historial.print');
        Route::get('historial/pdf', [HistorialAsistenciaController::class, 'exportPdf'])->name('historial.pdf');

        // CU12: Reportes y Analítica
        Route::get('reportes', [ReportesController::class, 'dashboard'])->name('reportes.index');
        Route::get('reportes/asistencia', [ReportesController::class, 'reporteAsistencia'])->name('reportes.asistencia');
        Route::get('reportes/asistencia/xls', [ReportesController::class, 'exportarAsistenciaExcel'])->name('reportes.asistencia.xls');
        Route::get('reportes/asistencia/pdf', [ReportesController::class, 'exportarAsistenciaPDF'])->name('reportes.asistencia.pdf');
        Route::get('reportes/horarios', [ReportesController::class, 'reporteHorarios'])->name('reportes.horarios');
        Route::get('reportes/horarios/pdf', [ReportesController::class, 'exportarHorariosPDF'])->name('reportes.horarios.pdf');
        Route::get('reportes/horarios/xls', [ReportesController::class, 'exportarHorariosExcel'])->name('reportes.horarios.xls');
        Route::get('reportes/horarios/csv', [ReportesController::class, 'exportarHorariosCsv'])->name('reportes.horarios.csv');
        Route::get('reportes/aulas', [ReportesController::class, 'reporteAulas'])->name('reportes.aulas');
        Route::get('reportes/aulas/xls', [ReportesController::class, 'exportarAulasExcel'])->name('reportes.aulas.xls');
        Route::get('reportes/aulas/csv', [ReportesController::class, 'exportarAulasCsv'])->name('reportes.aulas.csv');
        Route::get('reportes/aulas/pdf', [ReportesController::class, 'exportarAulasPDF'])->name('reportes.aulas.pdf');

        // CU13: Importar Datos Masivos
        Route::get('importaciones', [ImportacionController::class, 'index'])->name('importaciones.index');
        Route::get('importaciones/create', [ImportacionController::class, 'create'])->name('importaciones.create');
        Route::post('importaciones', [ImportacionController::class, 'store'])->name('importaciones.store');
        // Plantillas: priorizar master antes de la genérica y limitar 'tipo'
        Route::get('importaciones/template/master.xlsx', [ImportacionController::class, 'templateMasterXlsx'])->name('importaciones.template.master.xlsx');
        Route::get('importaciones/template/{tipo}.xlsx', [ImportacionController::class, 'templateXlsx'])->where('tipo','(docentes|materias|horarios)')->name('importaciones.template.xlsx');

        // CU14: Gestionar Usuarios
        Route::middleware('role:administrador,admin,director,director de carrera,coordinador')->group(function(){
            Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios'=>'usuario'])->except(['show']);
            Route::post('usuarios/{usuario}/toggle', [UsuarioController::class, 'toggle'])->name('usuarios.toggle');
            Route::post('usuarios/{usuario}/reset', [UsuarioController::class, 'resetPassword'])->name('usuarios.reset');
        });
        // CU14: Gestionar Roles (solo ADMIN)
        Route::middleware('role:administrador,admin')->group(function(){
            Route::resource('roles', RolController::class)->except(['show']);
        });

        // CU15: Consultar Bitácora (solo ADMIN o DECANO)
        Route::middleware('role:administrador,admin,decano')->group(function(){
            Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
            Route::get('bitacora/export', [BitacoraController::class, 'exportCsv'])->name('bitacora.export');
            Route::get('bitacora/xls', [BitacoraController::class, 'exportXlsx'])->name('bitacora.xlsx');
            Route::get('bitacora/pdf', [BitacoraController::class, 'exportPdf'])->name('bitacora.pdf');
            Route::get('bitacora/print', [BitacoraController::class, 'print'])->name('bitacora.print');
            Route::get('bitacora/{registro}', [BitacoraController::class, 'show'])->name('bitacora.show');
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
