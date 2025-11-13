@php($title = 'Carga Horaria')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Carga Horaria</h4>
    <small class="text-muted">Gestiona y visualiza las asignaciones de carga</small>
  </div>
  <a href="{{ route('carga.create') }}" class="btn btn-teal">Nueva Asignación</a>
</div>

<!-- Botón de creación visible solo en pantallas pequeñas -->
<div class="d-lg-none mb-2">
  <a href="{{ route('carga.create') }}" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Nueva Asignación</a>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-lg-3">
        <select name="docente_id" class="form-select">
          <option value="">Todos los docentes</option>
          @foreach($docentes as $d)
            <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>
              {{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <select name="gestion_id" class="form-select">
          <option value="">Todas las gestiones</option>
          @foreach($gestiones as $g)
            <option value="{{ $g->id_gestion }}" @selected(($gestionId ?? null)==$g->id_gestion)>{{ $g->codigo }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <select name="materia_id" class="form-select">
          <option value="">Todas las materias</option>
          @foreach($materias as $m)
            <option value="{{ $m->id_materia }}" @selected(($materiaId ?? null)==$m->id_materia)>
              {{ $m->nombre }} @if($m->codigo) ({{ $m->codigo }}) @endif
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <select name="grupo_id" class="form-select">
          <option value="">Todos los grupos</option>
          @foreach($grupos as $gr)
            <option value="{{ $gr->id_grupo }}" @selected(($grupoId ?? null)==$gr->id_grupo)>
              {{ $gr->nombre_grupo }} — {{ $gr->gestion->codigo ?? '' }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <select name="dia" class="form-select">
          <option value="">Todos los días</option>
          @foreach($dias as $d)
            <option value="{{ $d }}" @selected(($dia ?? '')===$d)>{{ $d }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-lg-2 d-grid mt-2">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

@if ($horarios->count() === 0)
  <div class="alert alert-info">No hay asignaciones.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Docente</th>
          <th>Materia / Grupo / Gestión</th>
          <th>Día</th>
          <th>Hora</th>
          <th>Aula</th>
          <th>Modalidad</th>
          <th>Estado</th>
          <th class="text-end" style="width:220px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($horarios as $h)
          <tr>
            <td>{{ $horarios->firstItem() + $loop->index }}</td>
            <td>{{ $h->docenteMateriaGestion->docente->usuario->nombre ?? '' }} {{ $h->docenteMateriaGestion->docente->usuario->apellido ?? '' }}</td>
            <td>
              <div class="fw-semibold text-uppercase">{{ $h->grupo->materia->nombre ?? '' }}</div>
              <div class="text-muted small">Grupo {{ $h->grupo->nombre_grupo ?? '' }} / {{ $h->grupo->gestion->codigo ?? '' }}</div>
            </td>
            <td>{{ $h->dia }}</td>
            <td>{{ substr($h->hora_inicio,0,5) }} - {{ substr($h->hora_fin,0,5) }}</td>
            <td>{{ $h->aula->nombre ?? '-' }}</td>
            <td>{{ $h->modalidad }}</td>
            <td>
              @php($cls = ($h->estado ?? 'PENDIENTE') === 'APROBADA' ? 'success' : (($h->estado ?? 'PENDIENTE') === 'RECHAZADA' ? 'danger' : 'warning text-dark'))
              <span class="badge bg-{{ $cls }}">{{ ucfirst(strtolower($h->estado ?? 'PENDIENTE')) }}</span>
            </td>
            <td class="text-end">
              @if(($h->estado ?? 'PENDIENTE') === 'APROBADA')
                <a href="{{ route('asistencias.qr', $h) }}" class="btn btn-sm btn-outline-success me-1">QR</a>
              @endif
              <a href="{{ route('carga.edit', $h) }}" class="btn btn-sm btn-outline-primary">Editar</a>
              <form action="{{ route('carga.destroy', $h) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar asignación?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div>{{ $horarios->links('vendor.pagination.teal') }}</div>
@endif
@endsection
