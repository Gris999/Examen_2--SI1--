@php($title = 'Historial de Aprobaciones')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Historial de Aprobaciones/Rechazos</h4>
    <small class="text-muted">Filtra por estado o gestión</small>
  </div>
  <a href="{{ route('aprobaciones.index') }}" class="btn btn-outline-secondary">Ver pendientes</a>
  </div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-md-3">
        <select name="estado" class="form-select">
          <option value="">Todos</option>
          @foreach(['APROBADA','RECHAZADA','PENDIENTE'] as $e)
            <option value="{{ $e }}" @selected(($estado ?? '')===$e)>{{ ucfirst(strtolower($e)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="gestion_id" class="form-select">
          <option value="">Todas las gestiones</option>
          @foreach($gestiones as $g)
            <option value="{{ $g->id_gestion }}" @selected(($gestion ?? null)==$g->id_gestion)>{{ $g->codigo }} @if($g->activo) (Activa) @endif</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

@if(($asignaciones->count() ?? 0) === 0)
  <div class="alert alert-info">Sin registros.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Docente</th>
          <th>Materia</th>
          <th>Gestión</th>
          <th>Estado</th>
          <th>Aprobado en</th>
        </tr>
      </thead>
      <tbody>
        @foreach($asignaciones as $a)
          <tr>
            <td>{{ $asignaciones->firstItem() + $loop->index }}</td>
            <td>{{ $a->docente->usuario->nombre ?? '' }} {{ $a->docente->usuario->apellido ?? '' }}</td>
            <td>{{ $a->materia->nombre ?? '' }}</td>
            <td>{{ $a->gestion->codigo ?? '' }}</td>
            <td>
              @php($cls = ($a->estado ?? 'PENDIENTE') === 'APROBADA' ? 'success' : (($a->estado ?? 'PENDIENTE') === 'RECHAZADA' ? 'danger' : 'secondary'))
              <span class="badge bg-{{ $cls }}">{{ ucfirst(strtolower($a->estado ?? 'PENDIENTE')) }}</span>
            </td>
            <td>{{ $a->aprobado_en ? \Carbon\Carbon::parse($a->aprobado_en)->format('Y-m-d H:i') : '-' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div>{{ $asignaciones->links('vendor.pagination.teal') }}</div>
@endif
@endsection
