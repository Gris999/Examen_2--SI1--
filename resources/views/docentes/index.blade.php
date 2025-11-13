@php($title = 'Docentes')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Docentes</h4>
    <small class="text-muted">Gestiona el personal docente de la institución</small>
  </div>
</div>

<!-- Botón de creación visible solo en pantallas pequeñas -->
<div class="d-lg-none mb-2">
  <a href="{{ route('docentes.create') }}" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Nuevo Docente</a>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-lg-8">
        <div class="input-group">
          <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control rounded-end-pill" placeholder="Buscar por nombre, correo, código, profesión...">
        </div>
      </div>
      <div class="col-lg-2 col-6">
        <button class="btn btn-teal w-100" type="submit"><i class="bi bi-search me-1"></i>Buscar</button>
      </div>
      <div class="col-lg-2 col-6">
        <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle me-1"></i>Limpiar</a>
      </div>
    </form>
  </div>
  </div>

@if ($docentes->count() === 0)
  <div class="alert alert-info">No hay docentes registrados.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Nombre</th>
          <th>Correo</th>
          <th style="width:120px">Código</th>
          <th>Profesión</th>
          <th style="width:160px">Grado Académico</th>
          <th style="width:120px">Estado</th>
          <th class="text-end" style="width:200px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($docentes as $d)
        @php($activo = (bool)($d->usuario->activo ?? true))
        <tr>
          <td>{{ $docentes->firstItem() + $loop->index }}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-inline-flex justify-content-center align-items-center" style="width:32px;height:32px;background:#0f766e;color:#fff;font-weight:600;">
                {{ strtoupper(substr($d->usuario->nombre ?? $d->usuario->correo,0,1)) }}
              </div>
              <div>
                <div class="fw-semibold">{{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }}</div>
              </div>
            </div>
          </td>
          <td class="text-muted"><i class="bi bi-envelope-open me-1"></i>{{ $d->usuario->correo ?? '' }}</td>
          <td>
            @if($d->codigo_docente)
              <span class="badge bg-light border text-muted">{{ $d->codigo_docente }}</span>
            @endif
          </td>
          <td>{{ $d->profesion }}</td>
          <td>
            @if($d->grado_academico)
              <span class="badge bg-light border text-success">{{ $d->grado_academico }}</span>
            @endif
          </td>
          <td>
            @if($activo)
              <span class="badge bg-success">Activo</span>
            @else
              <span class="badge bg-secondary">Inactivo</span>
            @endif
          </td>
          <td class="text-end">
            <div class="dropdown">
              <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a class="dropdown-item" href="{{ route('docentes.edit', $d) }}">
                    <i class="bi bi-pencil-square me-2"></i>Editar
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="{{ route('docentes.carga', $d) }}">
                    <i class="bi bi-list-check me-2"></i>Ver carga
                  </a>
                </li>
                <li>
                  <form action="{{ route('docentes.toggle', $d) }}" method="POST">
                    @csrf
                    <button class="dropdown-item" type="submit">
                      @if($activo)
                        <i class="bi bi-toggle2-off me-2"></i>Desactivar
                      @else
                        <i class="bi bi-toggle2-on me-2"></i>Activar
                      @endif
                    </button>
                  </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('docentes.destroy', $d) }}" method="POST" onsubmit="return confirm('¿Eliminar este docente?');">
                    @csrf
                    @method('DELETE')
                    <button class="dropdown-item text-danger" type="submit">
                      <i class="bi bi-trash me-2"></i>Eliminar
                    </button>
                  </form>
                </li>
              </ul>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div>
    {{ $docentes->links('vendor.pagination.teal') }}
  </div>
@endif
@endsection
