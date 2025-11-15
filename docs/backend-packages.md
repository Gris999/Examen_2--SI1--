# Organizacion de paquetes de uso (backend)
Este listado mapea los paquetes (modulos) del backend con los componentes principales, siguiendo el orden de la navegacion lateral para reforzar que la separacion es fisica por carpetas.

## 1. Autenticacion y Seguridad
- Las clases `AuthController`, `PasswordResetController`, `UsuarioController` y `RolController` estan en `app/Http/Controllers/Security/`. Ahi se concentran los middleware de rol y las rutas de login, logout y recuperacion de clave.
- El catalogo de usuarios, roles y permisos se nutre del middleware `role:` y de los filtros `auth_simple` aplicados en los demas controladores, de modo que cada paquete depende del sistema de autenticacion nativo de Laravel.
- No se requieren dependencias externas en este paquete; solo el core de `laravel/framework`.

## 2. Gestion academica
- Los controladores `DocenteController`, `MateriaController`, `GrupoController` y `AulaController` viven en `app/Http/Controllers/Academics/`.
- Alli se cargan los datos de catalogo (docentes, materias, grupos, aulas) y se validan antes de persistirlos; por ejemplo `DocenteController` usa `Docente::with('usuario')` en `app/Http/Controllers/Academics/DocenteController.php`.
- Este paquete depende unicamente del ORM de Laravel y las migraciones existentes.

## 3. Asignacion y Horarios
- Los controladores de `Assignments` (`HorarioController`, `AsignacionController`, `CargaHorariaController`, `AprobacionController`, `ImportacionController` y `ConsultaHorarioController`) gestionan la carga docente, las asignaciones aprobadas y la importacion de datos.
- El metodo `validateOverlap` en `app/Http/Controllers/Assignments/HorarioController.php` evita cruces entre docentes, aulas y grupos dentro del mismo dia.
- El procesamiento de plantillas Excel/CSV reutiliza `maatwebsite/excel` (referenciable desde `app/Http/Controllers/Assignments/ImportacionController.php` y la vista de importaciones).

## 4. Control de Asistencia
- Las clases en `app/Http/Controllers/Attendance/` (`AsistenciaController`, `HistorialAsistenciaController`, `DocentePortalController`) gestionan el registro digital (formulario o QR), la proteccion contra duplicados y el historico de marcaciones.
- `AsistenciaController` aplica los middleware de rol, valida la presencia por `id_horario` y se apoya en `auth()` para documentar quien genero cada registro y que metodo se uso (FORM, QR, MANUAL).
- El historial y el portal de docentes aprovechan los mismos modelos (`Horario`, `DocenteMateriaGestion`) para mostrar las sesiones del dia y los estados de ausencias.

## 5. Reportes y Datos
- `ReportesController`, `BitacoraController` y `DashboardController` estan en `app/Http/Controllers/Reports/`. Alli se concentran los KPIs, reportes de asistencia/horarios/aulas y auditorias.
- El dashboard expone KPIs como porcentaje de asistencia, docentes con mas horarios, aulas mas usadas y el ausentismo por materia (app/Http/Controllers/Reports/ReportesController.php:57-143).
- Los reportes detallados de asistencia y horarios se pueden exportar a PDF/Excel/CSV (app/Http/Controllers/Reports/ReportesController.php:145-470) y el modulo de bitacora genera respaldos en CSV, XLS y PDF.
- `canManageReports()` delimita la audiencia del paquete para administradores y roles definidos.

## Vision general
- Solo se usan dos dependencias externas: `dompdf/dompdf` para PDF y `maatwebsite/excel` para Excel/CSV; el resto es Laravel y los modelos propios.
