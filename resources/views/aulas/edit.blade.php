@php($title = 'Aulas')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Editar Aula</h4>
    <small class="text-muted">Actualiza los datos del aula</small>
  </div>
</div>

<form method="POST" action="{{ route('aulas.update', $aula) }}" class="row g-3">
  @csrf
  @method('PUT')
  <div class="col-md-3">
    <label class="form-label">Código</label>
    <input type="text" name="codigo" value="{{ old('codigo', $aula->codigo) }}" class="form-control" maxlength="50" required>
  </div>
  <div class="col-md-5">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre', $aula->nombre) }}" class="form-control" maxlength="120" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Tipo</label>
    <select name="tipo" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($tipos as $t)
        <option value="{{ $t }}" @selected(old('tipo', $aula->tipo)===$t)>{{ ucfirst(mb_strtolower($t)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Capacidad</label>
    <input type="number" name="capacidad" value="{{ old('capacidad', $aula->capacidad) }}" class="form-control" min="1" required>
  </div>
  <div class="col-md-9">
    <label class="form-label">Ubicación</label>
    <input type="text" name="ubicacion" value="{{ old('ubicacion', $aula->ubicacion) }}" class="form-control">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit"><i class="bi bi-save me-1"></i>Actualizar</button>
    <a href="{{ route('aulas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection
