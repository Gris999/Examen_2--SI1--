@php($title = 'Aprobaciones')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Aprobaciones de Asignaciones (DMG)</h4>
    <small class="text-muted">Aprueba o rechaza asignaciones pendientes</small>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('aprobaciones.historial') }}" class="btn btn-outline-secondary">Ver historial</a>
    <a href="{{ route('carga.index') }}" class="btn btn-outline-secondary">Ir a Carga (CU6)</a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-md-3">
        <select name="estado" class="form-select">
          @foreach(['PENDIENTE','APROBADA','RECHAZADA'] as $e)
            <option value="{{ $e }}" @selected(($estado ?? 'PENDIENTE')===$e)>{{ ucfirst(strtolower($e)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="gestion_id" class="form-select">
          <option value="">Todas las gestiones</option>
          @foreach(($gestiones ?? []) as $g)
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

@if (($asignaciones->count() ?? 0) === 0)
  <div class="alert alert-info">No hay registros para mostrar.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Docente</th>
          <th>Materia / Gestión</th>
          <th>Horarios</th>
          <th>Estado</th>
          <th class="text-end" style="width:220px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($asignaciones as $a)
          <tr>
            <td>{{ $asignaciones->firstItem() + $loop->index }}</td>
            <td>{{ $a->docente->usuario->nombre ?? '' }} {{ $a->docente->usuario->apellido ?? '' }}</td>
            <td>
              <div class="fw-semibold text-uppercase">{{ $a->materia->nombre ?? '' }}</div>
              <div class="text-muted small">{{ $a->gestion->codigo ?? '' }}</div>
            </td>
            <td>{{ $a->horarios_count }}</td>
            <td>
              @php($cls = ($a->estado ?? 'PENDIENTE') === 'APROBADA' ? 'success' : (($a->estado ?? 'PENDIENTE') === 'RECHAZADA' ? 'danger' : 'warning text-dark'))
              <span class="badge bg-{{ $cls }}">{{ ucfirst(strtolower($a->estado ?? 'PENDIENTE')) }}</span>
            </td>
            <td class="text-end">
              @php($roles = auth()->user()->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray())
              @php($puedeAprobar = in_array('administrador',$roles) || in_array('decano',$roles))
              @if(($a->estado ?? 'PENDIENTE') === 'PENDIENTE' && $puedeAprobar)
                <form action="{{ route('aprobaciones.approve', $a) }}" method="POST" class="d-inline">
                  @csrf
                  <button class="btn btn-sm btn-success">Aprobar</button>
                </form>
                <form action="{{ route('aprobaciones.reject', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Rechazar esta asignación?');">
                  @csrf
                  <button class="btn btn-sm btn-outline-danger">Rechazar</button>
                </form>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div>{{ $asignaciones->links('vendor.pagination.teal') }}</div>
@endif
@endsection
