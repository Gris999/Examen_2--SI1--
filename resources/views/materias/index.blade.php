@php($title = 'Materias')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Materias</h4>
    <small class="text-muted">Administra las asignaturas del plan académico</small>
  </div>
</div>

@php($canManage = auth()->check() && auth()->user()->roles()->whereIn('nombre',['administrador','admin','coordinador'])->exists())
<!-- Botón de creación visible solo en pantallas pequeñas -->
@if($canManage)
<div class="d-lg-none mb-2">
  <a href="{{ route('materias.create') }}" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Nueva Materia</a>
</div>
@endif

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-lg-5">
        <div class="input-group">
          <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control rounded-end-pill" placeholder="Buscar por nombre, código o descripción...">
        </div>
      </div>
      <div class="col-lg-3">
        <select name="facultad_id" class="form-select">
          <option value="">Todas las facultades</option>
          @foreach($facultades as $f)
            <option value="{{ $f->id_facultad }}" @selected(($facultadId ?? null)==$f->id_facultad)>
              {{ $f->nombre }} @if($f->sigla) ({{ $f->sigla }}) @endif
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <select name="carrera_id" class="form-select">
          <option value="">Todas las carreras</option>
          @foreach($carreras as $c)
            <option value="{{ $c->id_carrera }}" @selected(($carreraId ?? null)==$c->id_carrera)>
              {{ $c->nombre }} @if($c->sigla) ({{ $c->sigla }}) @endif
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-1 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

@if ($materias->count() === 0)
  <div class="alert alert-info">No hay materias registradas.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Nombre</th>
          <th style="width:140px">Código</th>
          <th style="width:160px">Carga Horaria</th>
          <th>Carrera</th>
          <th class="text-end" style="width:140px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($materias as $m)
        <tr>
          <td>{{ $materias->firstItem() + $loop->index }}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle border text-primary"><i class="bi bi-journal-text"></i></span>
              <div class="fw-semibold text-uppercase">{{ $m->nombre }}</div>
            </div>
          </td>
          <td>
            @if($m->codigo)
              <span class="badge bg-light border text-muted">{{ $m->codigo }}</span>
            @endif
          </td>
          <td>
            @if($m->carga_horaria)
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-muted"></i>
                <span>{{ $m->carga_horaria }} <small class="text-muted">hrs/semana</small></span>
              </div>
            @endif
          </td>
          <td>{{ $m->carrera->nombre ?? '' }} @if($m->carrera?->sigla) ({{ $m->carrera->sigla }}) @endif</td>
          <td class="text-end">
            <div class="dropdown">
              <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('materias.edit', $m) }}"><i class="bi bi-pencil-square me-2"></i>Editar</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('materias.destroy', $m) }}" method="POST" onsubmit="return confirm('¿Eliminar esta materia?');">
                    @csrf
                    @method('DELETE')
                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-trash me-2"></i>Eliminar</button>
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
    {{ $materias->links('vendor.pagination.teal') }}
  </div>
@endif
@endsection
