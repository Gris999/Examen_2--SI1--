@php($title = 'Grupos')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Grupos</h4>
    <small class="text-muted">Organiza y gestiona los grupos académicos</small>
  </div>
</div>

@php($canManage = auth()->check() && auth()->user()->roles()->whereIn('nombre',['administrador','admin','coordinador'])->exists())
<!-- Botón de creación visible solo en pantallas pequeñas -->
@if($canManage)
<div class="d-lg-none mb-2">
  <a href="{{ route('grupos.create') }}" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Nuevo Grupo</a>
</div>
@endif

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-lg-4">
        <div class="input-group">
          <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control rounded-end-pill" placeholder="Buscar por grupo o materia...">
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
      <div class="col-lg-2">
        <select name="gestion_id" class="form-select">
          <option value="">Todas las gestiones</option>
          @foreach($gestiones as $g)
            <option value="{{ $g->id_gestion }}" @selected(($gestionId ?? null)==$g->id_gestion)>{{ $g->codigo }} @if($g->activo) (Activa) @endif</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-1 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

@if ($grupos->count() === 0)
  <div class="alert alert-info">No hay grupos registrados.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Grupo</th>
          <th>Materia</th>
          <th style="width:140px">Gestión</th>
          <th style="width:160px">Cupo</th>
          <th class="text-end" style="width:220px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($grupos as $g)
        <tr>
          <td>{{ $grupos->firstItem() + $loop->index }}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="badge" style="background:#6f42c1;">{{ strtoupper(substr($g->nombre_grupo,0,2)) }}</span>
              <div class="fw-semibold">{{ $g->nombre_grupo }}</div>
            </div>
          </td>
          <td>{{ $g->materia->nombre ?? '' }} @if($g->materia?->codigo) ({{ $g->materia->codigo }}) @endif</td>
          <td>
            <span class="badge bg-info-subtle border text-info"><i class="bi bi-calendar2-week me-1"></i>{{ $g->gestion->codigo ?? '' }}</span>
          </td>
          <td>
            @php($cup = (int)($g->cupo ?? 0))
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-people text-muted"></i>
              <span>{{ $cup > 0 ? $cup : '—' }}/30</span>
              @if($cup >= 30)
                <span class="badge bg-danger">Lleno</span>
              @elseif($cup >= 25)
                <span class="badge bg-warning text-dark">Casi lleno</span>
              @endif
            </div>
          </td>
          <td class="text-end">
            <a href="{{ route('grupos.docentes', $g) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-lines-fill"></i> Docentes</a>
            @if($canManage)
              <a href="{{ route('grupos.edit', $g) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Editar</a>
              <form action="{{ route('grupos.destroy', $g) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este grupo?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
              </form>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div>
    {{ $grupos->links('vendor.pagination.teal') }}
  </div>
@endif
@endsection
