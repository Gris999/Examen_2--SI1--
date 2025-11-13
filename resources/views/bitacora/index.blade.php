@php($title = 'Bitácora del Sistema')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Bitácora</h4>
    <small class="text-muted">Acciones registradas en el sistema</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('bitacora.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>CSV</a>
    <a href="{{ route('bitacora.print', request()->query()) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>Imprimir</a>
    <a href="{{ route('bitacora.pdf', request()->query()) }}" class="btn btn-teal"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end m-0">
      <div class="col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" name="desde" value="{{ $desde }}" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Usuario</label>
        <select name="usuario_id" class="form-select">
          <option value="">Todos</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id_usuario }}" @selected(($usuarioId ?? null)==$u->id_usuario)>{{ $u->nombre }} {{ $u->apellido }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Acción</label>
        <select name="accion" class="form-select">
          <option value="">Todas</option>
          @foreach($acciones as $a)
            <option value="{{ $a }}" @selected(($accion ?? '')===$a)>{{ $a }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Tabla</label>
        <select name="tabla" class="form-select">
          <option value="">Todas</option>
          @foreach($tablas as $t)
            <option value="{{ $t }}" @selected(($tabla ?? '')===$t)>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:160px">Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Tabla</th>
          <th>ID Afectado</th>
          <th>IP</th>
          <th>Descripción</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr>
            <td>{{ $r->fecha }}</td>
            <td>{{ $r->usuario ?? '-' }}</td>
            <td><span class="badge bg-secondary">{{ $r->accion }}</span></td>
            <td>{{ $r->tabla_afectada }}</td>
            <td>{{ $r->id_afectado }}</td>
            <td>{{ $r->ip_origen }}</td>
            <td>{{ $r->descripcion }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">{{ $rows->links('vendor.pagination.teal') }}</div>
</div>
@endsection

