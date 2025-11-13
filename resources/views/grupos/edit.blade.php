@php($title = 'Grupos')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Editar Grupo</h4>
    <small class="text-muted">Actualiza los datos del grupo seleccionado</small>
  </div>
</div>

<form method="POST" action="{{ route('grupos.update', $grupo) }}" class="row g-3">
  @csrf
  @method('PUT')
  <div class="col-md-4">
    <label class="form-label">Gestión</label>
    <select name="id_gestion" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($gestiones as $g)
        <option value="{{ $g->id_gestion }}" @selected(old('id_gestion', $grupo->id_gestion)==$g->id_gestion)>
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
        <option value="{{ $m->id_materia }}" @selected(old('id_materia', $grupo->id_materia)==$m->id_materia)>
          {{ $m->nombre }} @if($m->codigo) ({{ $m->codigo }}) @endif
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Nombre Grupo</label>
    <input type="text" name="nombre_grupo" value="{{ old('nombre_grupo', $grupo->nombre_grupo) }}" class="form-control" maxlength="10" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Cupo</label>
    <input type="number" name="cupo" value="{{ old('cupo', $grupo->cupo) }}" class="form-control" min="1">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit"><i class="bi bi-save me-1"></i>Actualizar</button>
    <a href="{{ route('grupos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

