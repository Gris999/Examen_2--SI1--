@php($title = 'Materias')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Editar Materia</h4>
    <small class="text-muted">Actualiza los datos de la asignatura</small>
  </div>
</div>

<form method="POST" action="{{ route('materias.update', $materia) }}" class="row g-3">
  @csrf
  @method('PUT')
  <div class="col-md-6">
    <label class="form-label">Carrera</label>
    <select name="id_carrera" class="form-select" required>
      <option value="">Selecciona...</option>
      @foreach($carreras as $c)
        <option value="{{ $c->id_carrera }}" @selected(old('id_carrera', $materia->id_carrera)==$c->id_carrera)>
          {{ $c->nombre }} @if($c->sigla) ({{ $c->sigla }}) @endif
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre', $materia->nombre) }}" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Código</label>
    <input type="text" name="codigo" value="{{ old('codigo', $materia->codigo) }}" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Carga Horaria</label>
    <input type="number" min="1" name="carga_horaria" value="{{ old('carga_horaria', $materia->carga_horaria) }}" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Descripción</label>
    <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion', $materia->descripcion) }}</textarea>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Actualizar</button>
    <a href="{{ route('materias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection
