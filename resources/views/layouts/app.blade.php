<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sistema Académico' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
  <style>
    .toast-container { z-index: 1080; }
    html, body { width:100%; max-width:100%; overflow-x:hidden; }
    .sidebar { width: 260px; min-height: 100vh; position: fixed; top:0; left:0; background: #0b2239; display:flex; flex-direction:column; overflow-y:auto; z-index:1050; transition: transform .25s ease; }
    .sidebar .user { padding: 16px; border-bottom: 1px solid rgba(255,255,255,.08); }
    .sidebar .user .circle { width:44px;height:44px;border-radius:50%;background:#0f766e;color:#fff;display:flex;align-items:center;justify-content:center; font-weight:600; font-size:18px; }
    .sidebar a.nav-link { color: #d7e1ea; border-radius: 10px; padding: 11px 12px; font-size: 0.95rem; }
    .sidebar a.nav-link.active, .sidebar a.nav-link:hover { background:#113252; color:#fff; }
    .content-wrap { margin-left: 260px; position: relative; width: calc(100% - 260px); overflow-x:hidden; }
    main.content-wrap { min-height: 100vh; }
    .sidebar-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index:1040; pointer-events:none; opacity:0; transition: opacity .2s ease; }
    .sidebar-backdrop.visible { opacity:1; pointer-events:auto; }
    .mobile-dashboard-toggle { position: fixed; top: 1rem; left: 1rem; z-index: 1090; width: 40px; height: 40px; display:flex; align-items:center; justify-content:center; background: #495057; border: none; color: #fff; }
    .mobile-dashboard-toggle:focus { outline: none; box-shadow: 0 0 0 0.25rem rgba(15, 118, 110, 0.25); }
    @media(max-width: 991px){
      .sidebar { transform: translateX(-100%); }
      .sidebar.sidebar-open { transform: translateX(0); }
      .content-wrap { margin-left: 0; width: 100%; }
    }
  </style>

  <button class="mobile-dashboard-toggle d-lg-none rounded-circle btn btn-dark" type="button" data-sidebar-toggle aria-label="Abrir menú">
    <i class="bi bi-list"></i>
  </button>

<div class="toast-container position-fixed top-0 end-0 p-3">
  @if (session('status'))
    <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
      <div class="d-flex">
        <div class="toast-body">{{ session('status') }}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  @endif
  @if (session('warning'))
    <div class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
      <div class="d-flex">
        <div class="toast-body">{{ session('warning') }}</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  @endif
  @if ($errors->any())
    <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
      <div class="d-flex">
        <div class="toast-body">
          <strong>Ocurrieron errores:</strong>
          <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  @endif
</div>
@auth
  <!-- Mobile topbar -->
  <div class="d-lg-none topbar d-flex justify-content-between align-items-center px-3 py-2">
    <button class="btn btn-link text-white p-0" type="button" data-sidebar-toggle>
      <i class="bi bi-list fs-4"></i>
    </button>
    <div class="fw-semibold">{{ $title ?? (ucfirst(str_replace('.', ' ', request()->route()->getName())) ) }}</div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
    </form>
  </div>

  <aside class="sidebar d-flex flex-column text-white" id="mainSidebar">
    <div class="user d-flex align-items-center gap-2">
      <div class="circle">{{ strtoupper(substr(auth()->user()->nombre ?? auth()->user()->correo,0,1)) }}</div>
      <div>
        <div class="fw-semibold">{{ auth()->user()->nombre ?? auth()->user()->correo }}</div>
        <small class="text-muted">{{ optional(auth()->user()->roles()->first())->nombre ?? 'Usuario' }}</small>
      </div>
    </div>
    <div class="px-2 pt-2">
      <form method="POST" action="{{ route('logout') }}" class="d-grid">
        @csrf
        <button class="btn btn-danger btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
      </form>
    </div>
    <div class="d-flex justify-content-end d-lg-none px-2">
      <button class="btn btn-link text-white p-0" type="button" data-sidebar-close aria-label="Cerrar menú">
        <i class="bi bi-x-lg fs-4"></i>
      </button>
    </div>
    @php
      $roles = auth()->user()->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
      $isAdmin = in_array('administrador', $roles) || in_array('admin', $roles);
      $isDecano = in_array('decano', $roles);
      $isDirector = in_array('director de carrera', $roles) || in_array('director', $roles) || in_array('coordinador', $roles);
      $isDocente = in_array('docente', $roles);
      $docenteId = auth()->user()->docente->id_docente ?? null;
    @endphp
    @if($isDocente)
      <nav class="p-2">
        <a class="nav-link {{ request()->routeIs('docente.portal') ? 'active' : '' }}" href="{{ route('docente.portal') }}"><i class="bi bi-house me-2"></i>Horario Semanal</a>
        <a class="nav-link {{ request()->routeIs('asistencias.create') ? 'active' : '' }}" href="{{ route('asistencias.create') }}"><i class="bi bi-clipboard-check me-2"></i>Registro de asistencia</a>
        <a class="nav-link {{ request()->routeIs('historial.*') ? 'active' : '' }}" href="{{ route('historial.index') }}"><i class="bi bi-collection me-2"></i>Historial de asistencia</a>
      </nav>
    @else
    <nav class="p-2">
      <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house me-2"></i>Inicio</a>

      <div class="accordion mt-2" id="menuAccordion">
        <div class="accordion-item bg-transparent border-0">
          <h2 class="accordion-header" id="acc-auth">
            <button class="accordion-button collapsed py-2 bg-transparent text-white-50" type="button" data-bs-toggle="collapse" data-bs-target="#col-auth" aria-expanded="false" aria-controls="col-auth">
              Autenticación y Seguridad
            </button>
          </h2>
          <div id="col-auth" class="accordion-collapse collapse" aria-labelledby="acc-auth" data-bs-parent="#menuAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDirector)
                <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}"><i class="bi bi-person-lines-fill me-2"></i>Usuarios</a>
                @if($isAdmin)
                  <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="bi bi-shield-lock me-2"></i>Roles</a>
                @endif
              @endif
              @if($isAdmin || $isDecano)
                @if (Route::has('bitacora.index'))
                  <a class="nav-link {{ request()->routeIs('bitacora.*') ? 'active' : '' }}" href="{{ route('bitacora.index') }}"><i class="bi bi-activity me-2"></i>Bitácora</a>
                @endif
              @endif
            </div>
          </div>
        </div>

        <div class="accordion-item bg-transparent border-0">
          <h2 class="accordion-header" id="acc-acad">
            <button class="accordion-button collapsed py-2 bg-transparent text-white-50" type="button" data-bs-toggle="collapse" data-bs-target="#col-acad" aria-expanded="false" aria-controls="col-acad">
              Gestión académica
            </button>
          </h2>
          <div id="col-acad" class="accordion-collapse collapse" aria-labelledby="acc-acad" data-bs-parent="#menuAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDecano)
                @if($isAdmin || $isDirector)
                  <a class="nav-link {{ request()->routeIs('docentes.*') ? 'active' : '' }}" href="{{ route('docentes.index') }}"><i class="bi bi-people me-2"></i>Docentes</a>
                @elseif($isDecano)
                  <a class="nav-link {{ request()->routeIs('consulta.docentes') ? 'active' : '' }}" href="{{ route('consulta.docentes') }}"><i class="bi bi-people me-2"></i>Docentes</a>
                @endif
              @endif
              @if($isAdmin || $isDirector || $isDecano)
                @if($isAdmin || $isDirector)
                  <a class="nav-link {{ request()->routeIs('materias.*') ? 'active' : '' }}" href="{{ route('materias.index') }}"><i class="bi bi-journal-text me-2"></i>Materias</a>
                @elseif($isDecano)
                  <a class="nav-link {{ request()->routeIs('consulta.materias') ? 'active' : '' }}" href="{{ route('consulta.materias') }}"><i class="bi bi-journal-text me-2"></i>Materias</a>
                @endif
              @endif
              @if($isAdmin || $isDirector)
                @if($isAdmin || $isDirector)
                  <a class="nav-link {{ request()->routeIs('grupos.*') ? 'active' : '' }}" href="{{ route('grupos.index') }}"><i class="bi bi-collection me-2"></i>Grupos</a>
                @elseif($isDecano)
                  <a class="nav-link {{ request()->routeIs('consulta.grupos') ? 'active' : '' }}" href="{{ route('consulta.grupos') }}"><i class="bi bi-collection me-2"></i>Grupos</a>
                @endif
              @endif
              @if($isAdmin)
                @if($isAdmin || $isDirector)
                  <a class="nav-link {{ request()->routeIs('aulas.*') ? 'active' : '' }}" href="{{ route('aulas.index') }}"><i class="bi bi-building me-2"></i>Aulas</a>
                @elseif($isDecano)
                  <a class="nav-link {{ request()->routeIs('consulta.aulas') ? 'active' : '' }}" href="{{ route('consulta.aulas') }}"><i class="bi bi-building me-2"></i>Aulas</a>
                @endif
              @endif
            </div>
          </div>
        </div>

        <div class="accordion-item bg-transparent border-0">
          <h2 class="accordion-header" id="acc-hrs">
            <button class="accordion-button collapsed py-2 bg-transparent text-white-50" type="button" data-bs-toggle="collapse" data-bs-target="#col-hrs" aria-expanded="false" aria-controls="col-hrs">
              Asignación y Horarios
            </button>
          </h2>
          <div id="col-hrs" class="accordion-collapse collapse" aria-labelledby="acc-hrs" data-bs-parent="#menuAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDirector)
                <a class="nav-link {{ request()->routeIs('carga.*') ? 'active' : '' }}" href="{{ route('carga.index') }}"><i class="bi bi-calendar-week me-2"></i>Carga Horaria</a>
              @endif
              @if($isAdmin || $isDecano)
                <a class="nav-link {{ request()->routeIs('aprobaciones.*') ? 'active' : '' }}" href="{{ route('aprobaciones.index') }}"><i class="bi bi-check2-circle me-2"></i>Aprobaciones</a>
              @endif
              @if($isAdmin || $isDirector)
                <a class="nav-link {{ request()->routeIs('horarios.*') ? 'active' : '' }}" href="{{ route('horarios.index') }}"><i class="bi bi-calendar-event me-2"></i>Horarios</a>
              @endif
              @if($isAdmin || $isDecano || $isDirector)
                <a class="nav-link {{ request()->routeIs('asignaciones.*') ? 'active' : '' }}" href="{{ route('asignaciones.index') }}"><i class="bi bi-people-gear me-2"></i>Asignaciones</a>
                <a class="nav-link {{ request()->routeIs('consultas.horarios.*') ? 'active' : '' }}" href="{{ route('consultas.horarios.index') }}"><i class="bi bi-table me-2"></i>Consulta de Horarios</a>
              @endif
            </div>
          </div>
        </div>

        <div class="accordion-item bg-transparent border-0">
          <h2 class="accordion-header" id="acc-asist">
            <button class="accordion-button collapsed py-2 bg-transparent text-white-50" type="button" data-bs-toggle="collapse" data-bs-target="#col-asist" aria-expanded="false" aria-controls="col-asist">
              Control de Asistencia
            </button>
          </h2>
          <div id="col-asist" class="accordion-collapse collapse" aria-labelledby="acc-asist" data-bs-parent="#menuAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDecano || $isDocente)
                <a class="nav-link {{ request()->routeIs('asistencias.*') ? 'active' : '' }}" href="{{ route('asistencias.index') }}"><i class="bi bi-clipboard-check me-2"></i>Asistencia</a>
              @endif
              @if($isAdmin || $isDecano || $isDirector || $isDocente)
                <a class="nav-link {{ request()->routeIs('historial.*') ? 'active' : '' }}" href="{{ route('historial.index') }}"><i class="bi bi-collection me-2"></i>Historial</a>
              @endif
              @if($isDocente)
                <a class="nav-link {{ request()->routeIs('docente.portal') ? 'active' : '' }}" href="{{ route('docente.portal') }}"><i class="bi bi-person-lines-fill me-2"></i>Horario Semanal</a>
              @endif
            </div>
          </div>
        </div>

        <div class="accordion-item bg-transparent border-0">
          <h2 class="accordion-header" id="acc-rep">
            <button class="accordion-button collapsed py-2 bg-transparent text-white-50" type="button" data-bs-toggle="collapse" data-bs-target="#col-rep" aria-expanded="false" aria-controls="col-rep">
              Reportes y Datos
            </button>
          </h2>
          <div id="col-rep" class="accordion-collapse collapse" aria-labelledby="acc-rep" data-bs-parent="#menuAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDirector)
                <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}"><i class="bi bi-graph-up-arrow me-2"></i>Reportes</a>
                <a class="nav-link {{ request()->routeIs('importaciones.*') ? 'active' : '' }}" href="{{ route('importaciones.index') }}"><i class="bi bi-upload me-2"></i>Importar Datos</a>
              @endif
            </div>
          </div>
        </div>
      </div>
    </nav>
    @endif
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <!-- Offcanvas mobile menu -->
  <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="mobileMenuLabel">Menú</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
        <div class="offcanvas-body d-flex flex-column">
      @php
        $roles = auth()->user()->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
        $isAdmin = in_array('administrador', $roles) || in_array('admin', $roles);
        $isDecano = in_array('decano', $roles);
        $isDirector = in_array('director de carrera', $roles) || in_array('director', $roles) || in_array('coordinador', $roles);
        $isDocente = in_array('docente', $roles);
      @endphp
      <div class="mb-2">
        <a class="list-group-item list-group-item-action {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house me-2"></i>Inicio</a>
      </div>
      <div class="accordion" id="mobileAccordion">
        <div class="accordion-item">
          <h2 class="accordion-header" id="m-auth-h"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#m-auth" aria-expanded="false" aria-controls="m-auth">Autenticación y Seguridad</button></h2>
          <div id="m-auth" class="accordion-collapse collapse" data-bs-parent="#mobileAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDirector)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">Usuarios</a>
                @if($isAdmin)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">Roles</a>
                @endif
              @endif
              @if($isAdmin || $isDecano)
                @if (Route::has('bitacora.index'))
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('bitacora.*') ? 'active' : '' }}" href="{{ route('bitacora.index') }}">Bitácora</a>
                @endif
              @endif
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="m-acad-h"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#m-acad" aria-expanded="false" aria-controls="m-acad">Gestión académica</button></h2>
          <div id="m-acad" class="accordion-collapse collapse" data-bs-parent="#mobileAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDecano)
                @if($isAdmin || $isDirector)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('docentes.*') ? 'active' : '' }}" href="{{ route('docentes.index') }}">Docentes</a>
                @elseif($isDecano)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('consulta.docentes') ? 'active' : '' }}" href="{{ route('consulta.docentes') }}">Docentes</a>
                @endif
              @endif
              @if($isAdmin || $isDirector || $isDecano)
                @if($isAdmin || $isDirector)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('materias.*') ? 'active' : '' }}" href="{{ route('materias.index') }}">Materias</a>
                @elseif($isDecano)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('consulta.materias') ? 'active' : '' }}" href="{{ route('consulta.materias') }}">Materias</a>
                @endif
              @endif
              @if($isAdmin || $isDirector)
                @if($isAdmin || $isDirector)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('grupos.*') ? 'active' : '' }}" href="{{ route('grupos.index') }}">Grupos</a>
                @elseif($isDecano)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('consulta.grupos') ? 'active' : '' }}" href="{{ route('consulta.grupos') }}">Grupos</a>
                @endif
              @endif
              @if($isAdmin)
                @if($isAdmin || $isDirector)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('aulas.*') ? 'active' : '' }}" href="{{ route('aulas.index') }}">Aulas</a>
                @elseif($isDecano)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('consulta.aulas') ? 'active' : '' }}" href="{{ route('consulta.aulas') }}">Aulas</a>
                @endif
              @endif
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="m-hrs-h"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#m-hrs" aria-expanded="false" aria-controls="m-hrs">Asignación y Horarios</button></h2>
          <div id="m-hrs" class="accordion-collapse collapse" data-bs-parent="#mobileAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDirector)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('carga.*') ? 'active' : '' }}" href="{{ route('carga.index') }}">Carga Horaria</a>
              @endif
              @if($isAdmin || $isDecano)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('aprobaciones.*') ? 'active' : '' }}" href="{{ route('aprobaciones.index') }}">Aprobaciones</a>
              @endif
              @if($isAdmin || $isDirector)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('horarios.*') ? 'active' : '' }}" href="{{ route('horarios.index') }}">Horarios</a>
              @endif
              @if($isAdmin || $isDecano || $isDirector)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('asignaciones.*') ? 'active' : '' }}" href="{{ route('asignaciones.index') }}">Asignaciones</a>
                <a class="list-group-item list-group-item-action {{ request()->routeIs('consultas.horarios.*') ? 'active' : '' }}" href="{{ route('consultas.horarios.index') }}">Consulta de Horarios</a>
              @endif
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="m-asist-h"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#m-asist" aria-expanded="false" aria-controls="m-asist">Control de Asistencia</button></h2>
          <div id="m-asist" class="accordion-collapse collapse" data-bs-parent="#mobileAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDecano || $isDocente)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('asistencias.*') ? 'active' : '' }}" href="{{ route('asistencias.index') }}">Asistencia</a>
              @endif
              @if($isAdmin || $isDecano || $isDirector || $isDocente)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('historial.*') ? 'active' : '' }}" href="{{ route('historial.index') }}">Historial</a>
                @if($isDocente)
                  <a class="list-group-item list-group-item-action {{ request()->routeIs('docente.portal') ? 'active' : '' }}" href="{{ route('docente.portal') }}">Horario Semanal</a>
                @endif
              @endif
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="m-rep-h"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#m-rep" aria-expanded="false" aria-controls="m-rep">Reportes y Datos</button></h2>
          <div id="m-rep" class="accordion-collapse collapse" data-bs-parent="#mobileAccordion">
            <div class="accordion-body py-1">
              @if($isAdmin || $isDirector)
                <a class="list-group-item list-group-item-action {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">Reportes</a>
                <a class="list-group-item list-group-item-action {{ request()->routeIs('importaciones.*') ? 'active' : '' }}" href="{{ route('importaciones.index') }}">Importar Datos</a>
              @endif
            </div>
          </div>
        </div>
      </div>
      <form method="POST" action="{{ route('logout') }}" class="pt-3">
        @csrf
        <button class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
      </form>
    </div>
  </div>
@endauth

@auth
  <div class="topbar d-none d-lg-block content-wrap">
    <div class="d-flex justify-content-between align-items-center">
      <div class="crumb">
        <i class="bi bi-chevron-right"></i> {{ $title ?? (ucfirst(str_replace('.', ' ', request()->route()->getName())) ) }}
      </div>
      <div class="d-flex gap-2">
        @php $r = request()->route()->getName(); @endphp
        @if(str_starts_with($r,'docentes.') && ($isAdmin ?? false))
          <a href="{{ route('docentes.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo Docente</a>
        @elseif(str_starts_with($r,'materias.') && ($isAdmin ?? false))
          <a href="{{ route('materias.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Nueva Materia</a>
        @elseif(str_starts_with($r,'grupos.') && (($isAdmin ?? false) || ($isDirector ?? false)))
          <a href="{{ route('grupos.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo Grupo</a>
        @elseif(str_starts_with($r,'aulas.') && ($isAdmin ?? false))
          <a href="{{ route('aulas.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Nueva Aula</a>
        @elseif(str_starts_with($r,'carga.'))
          <a href="{{ route('carga.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Nueva Asignación</a>
        @elseif(str_starts_with($r,'horarios.'))
          <a href="{{ route('horarios.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo Horario</a>
        @elseif(str_starts_with($r,'asistencias.') && !request()->routeIs('asistencias.create') && (($isAdmin ?? false) || ($isDirector ?? false) || ($isDocente ?? false)))
          <a href="{{ route('asistencias.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Registrar Asistencia</a>
        @endif
      </div>
    </div>
  </div>
@endauth

<main class="content-wrap container-fluid pt-3">

  {{ $slot ?? '' }}
  @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (function(){
    // Toasters
    var els = document.querySelectorAll('.toast');
    els.forEach(function(el){ new bootstrap.Toast(el).show(); });

    // Persist accordion state (desktop + mobile)
    function setupAccordionState(prefix, accordionId){
      var acc = document.getElementById(accordionId);
      if (!acc) return;
      var key = 'menu:'+prefix+':openId';

      // Restore last open panel
      try {
        var openId = localStorage.getItem(key);
        if (openId){
          var target = document.getElementById(openId);
          if (target && !target.classList.contains('show')){
            target.classList.add('show');
            var btn = document.querySelector('[data-bs-target="#'+openId+'"]');
            if (btn) btn.classList.remove('collapsed');
          }
        }
      } catch (e) {}

      // Save when a panel is opened/closed
      acc.addEventListener('shown.bs.collapse', function(e){
        try { localStorage.setItem(key, e.target.id); } catch (e) {}
      });
      acc.addEventListener('hidden.bs.collapse', function(e){
        try {
          var cur = localStorage.getItem(key);
          if (cur === e.target.id) localStorage.removeItem(key);
        } catch (e) {}
      });
    }

  setupAccordionState('desktop','menuAccordion');
  setupAccordionState('mobile','mobileAccordion');

  var sidebar = document.querySelector('.sidebar');
  var toggleSidebar = document.querySelector('[data-sidebar-toggle]');
  var closeSidebarBtn = document.querySelector('[data-sidebar-close]');
  var backdrop = document.querySelector('[data-sidebar-backdrop]');
  function openSidebar(){ sidebar && sidebar.classList.add('sidebar-open'); backdrop && backdrop.classList.add('visible'); }
  function closeSidebar(){ sidebar && sidebar.classList.remove('sidebar-open'); backdrop && backdrop.classList.remove('visible'); }
  if(toggleSidebar){
    toggleSidebar.addEventListener('click', function(){ sidebar && (sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar()); });
  }
  if(closeSidebarBtn){ closeSidebarBtn.addEventListener('click', closeSidebar); }
  if(backdrop){ backdrop.addEventListener('click', closeSidebar); }
  var media = window.matchMedia('(min-width: 992px)');
  function resetSidebar(e){ if(e.matches){ closeSidebar(); } }
  if(media.addEventListener){ media.addEventListener('change', resetSidebar); }
  else if(media.addListener){ media.addListener(resetSidebar); }
  resetSidebar(media);
})();
</script>
@yield('scripts')
</body>
</html>
