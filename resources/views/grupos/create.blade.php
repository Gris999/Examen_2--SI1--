@php($title = 'Grupos')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Nuevo Grupo</h4>
    <small class="text-muted">Crea un grupo asociado a una materia y gestión</small>
  </div>
</div>

<form method="POST" action="{{ route('grupos.store') }}" class="row g-3">
  @csrf
  <div class="col-md-4">
    <label class="form-label">Gestión</label>
    <select name="id_gestion" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($gestiones as $g)
        <option value="{{ $g->id_gestion }}" @selected(old('id_gestion')==$g->id_gestion)>
          {{ $g->codigo }} @if($g->activo) (Activa) @endif
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Materia</label>
    <select name="id_materia" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($materias as $m)
        <option value="{{ $m->id_materia }}" @selected(old('id_materia')==$m->id_materia)>
          {{ $m->nombre }} @if($m->codigo) ({{ $m->codigo }}) @endif
        </option>
      @endforeach
    </select>
    <div class="form-text">Para buscar por facultad/carrera usa los filtros del listado.</div>
  </div>
  <div class="col-md-2">
    <label class="form-label">Nombre Grupo</label>
    <input type="text" name="nombre_grupo" value="{{ old('nombre_grupo') }}" class="form-control" maxlength="10" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Cupo</label>
    <input type="number" name="cupo" value="{{ old('cupo') }}" class="form-control" min="1">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit"><i class="bi bi-check2 me-1"></i>Guardar</button>
    <a href="{{ route('grupos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

