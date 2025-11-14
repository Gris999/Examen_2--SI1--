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
    <a href="{{ route('bitacora.xlsx', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel (XLS)</a>
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
      <div class="col-md-2">
        <label class="form-label">IP Origen</label>
        <input type="text" name="ip" value="{{ $ip }}" class="form-control" placeholder="e.g. 127.0.0.1">
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
          <th style="width:110px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @if(!$rows->count())
          <tr><td colspan="8" class="text-muted">Sin registros.</td></tr>
        @else
          @foreach($rows as $r)
          <tr>
            <td>{{ $r->fecha }}</td>
            <td>{{ $r->usuario ?? '-' }}</td>
            <td>
              @php($acc = strtoupper($r->accion ?? ''))
              @php($cls = 'bg-secondary')
              @if(str_contains($acc,'LOGIN') || str_contains($acc,'LOGOUT'))
                @php($cls='bg-primary')
              @elseif(str_contains($acc,'CREAR') || $acc==='INSERT' || str_contains($acc,'ASIGNAR') || str_contains($acc,'APROBAR'))
                @php($cls='bg-success')
              @elseif(str_contains($acc,'EDIT') || $acc==='UPDATE')
                @php($cls='bg-warning text-dark')
              @elseif(str_contains($acc,'ELIM') || $acc==='DELETE' || str_contains($acc,'RECHAZAR'))
                @php($cls='bg-danger')
              @elseif(str_contains($acc,'IMPORT'))
                @php($cls='bg-info text-dark')
              @elseif(str_contains($acc,'RESET'))
                @php($cls='bg-dark')
              @endif
              <span class="badge {{ $cls }}">{{ $r->accion }}</span>
            </td>
            <td>{{ $r->tabla_afectada }}</td>
            <td>{{ $r->id_afectado }}</td>
            <td>{{ $r->ip_origen }}</td>
            <td>{{ $r->descripcion }}</td>
            <td class="text-end">
              <a href="{{ route('bitacora.show', $r->id_bitacora) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
          @endforeach
        @endif
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">{{ $rows->links('vendor.pagination.teal') }}</div>
</div>
@endsection
