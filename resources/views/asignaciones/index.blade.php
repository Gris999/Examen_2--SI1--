@php($title = 'Asignaciones (DMG)')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Asignaciones (Docente–Materia–Gestión)</h4>
    <small class="text-muted">Crea asignaciones (quedan en PENDIENTE hasta su aprobación)</small>
  </div>
  <a href="{{ route('asignaciones.create') }}" class="btn btn-teal">Asignar</a>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-lg-3">
        <select name="estado" class="form-select">
          <option value="">Todos</option>
          @foreach(['PENDIENTE','APROBADA','RECHAZADA'] as $e)
            <option value="{{ $e }}" @selected(($estado ?? '')===$e)>{{ ucfirst(strtolower($e)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <select name="gestion_id" class="form-select">
          <option value="">Todas las gestiones</option>
          @foreach($gestiones as $g)
            <option value="{{ $g->id_gestion }}" @selected(($gestionId ?? null)==$g->id_gestion)>{{ $g->codigo }} @if($g->activo) (Activa) @endif</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <select name="docente_id" class="form-select">
          <option value="">Todos los docentes</option>
          @foreach($docentes as $d)
            <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>{{ $d->usuario->nombre }} {{ $d->usuario->apellido }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

@if($asignaciones->count()===0)
  <div class="alert alert-info">No hay asignaciones para mostrar.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Docente</th>
          <th>Materia</th>
          <th>Gestión</th>
          <th>Estado</th>
          <th class="text-end" style="width:160px">Acciones</th>
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
              @php($cls = $a->estado==='APROBADA' ? 'success' : ($a->estado==='RECHAZADA' ? 'danger' : 'warning text-dark'))
              <span class="badge bg-{{ $cls }}">{{ ucfirst(strtolower($a->estado)) }}</span>
            </td>
            <td class="text-end">
              @if($a->estado==='PENDIENTE')
                <a href="{{ route('asignaciones.edit', $a) }}" class="btn btn-sm btn-outline-primary me-1">Editar</a>
                <form action="{{ route('asignaciones.destroy', $a) }}" method="POST" onsubmit="return confirm('¿Eliminar asignación?');" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
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
