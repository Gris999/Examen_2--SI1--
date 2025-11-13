@php($title = 'Aulas')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Aulas</h4>
    <small class="text-muted">Gestiona los espacios físicos y virtuales</small>
  </div>
</div>

<!-- Botón de creación visible solo en pantallas pequeñas -->
<div class="d-lg-none mb-2">
  <a href="{{ route('aulas.create') }}" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Nueva Aula</a>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-center m-0">
      <div class="col-lg-6">
        <div class="input-group">
          <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control rounded-end-pill" placeholder="Buscar por código, nombre, tipo o ubicación...">
        </div>
      </div>
      <div class="col-lg-4">
        <select name="tipo" class="form-select">
          <option value="">Todos los tipos</option>
          @foreach($tipos as $t)
            <option value="{{ $t }}" @selected($tipo===$t)>{{ ucfirst(mb_strtolower($t)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
    <div class="mt-2">
      <a href="{{ route('aulas.disponibilidad', request()->only('dia','hora_inicio','hora_fin')) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-eye me-1"></i> Ver disponibilidad
      </a>
    </div>
  </div>
</div>

@if ($aulas->count() === 0)
  <div class="alert alert-info">No hay aulas registradas.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th style="width:140px">Código</th>
          <th>Nombre</th>
          <th style="width:140px">Tipo</th>
          <th style="width:140px">Capacidad</th>
          <th>Ubicación</th>
          <th style="width:120px">Estado</th>
          <th class="text-end" style="width:160px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($aulas as $a)
        <tr>
          <td>{{ $aulas->firstItem() + $loop->index }}</td>
          <td>{{ $a->codigo }}</td>
          <td>{{ $a->nombre }}</td>
          <td>
            @if($a->tipo)
              <span class="badge bg-light border text-muted">{{ $a->tipo }}</span>
            @endif
          </td>
          <td>{{ $a->capacidad }}</td>
          <td>{{ $a->ubicacion }}</td>
          <td>
            @if(($usoMap[$a->id_aula] ?? 0) > 0)
              <span class="badge bg-warning text-dark">En uso</span>
            @else
              <span class="badge bg-success">Libre</span>
            @endif
          </td>
          <td class="text-end">
            <a href="{{ route('aulas.edit', $a) }}" class="btn btn-sm btn-outline-primary">Editar</a>
            <form action="{{ route('aulas.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta aula?');">
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
  <div>
    {{ $aulas->links('vendor.pagination.teal') }}
  </div>
@endif
@endsection
