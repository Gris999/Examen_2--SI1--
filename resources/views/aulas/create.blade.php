@php($title = 'Aulas')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Nueva Aula</h4>
    <small class="text-muted">Registra un espacio físico o virtual</small>
  </div>
</div>

<form method="POST" action="{{ route('aulas.store') }}" class="row g-3">
  @csrf
  <div class="col-md-3">
    <label class="form-label">Código</label>
    <input type="text" name="codigo" value="{{ old('codigo') }}" class="form-control" maxlength="50" required>
  </div>
  <div class="col-md-5">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" maxlength="120" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Tipo</label>
    <select name="tipo" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($tipos as $t)
        <option value="{{ $t }}" @selected(old('tipo')===$t)>{{ ucfirst(mb_strtolower($t)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Capacidad</label>
    <input type="number" name="capacidad" value="{{ old('capacidad') }}" class="form-control" min="1" required>
  </div>
  <div class="col-md-9">
    <label class="form-label">Ubicación</label>
    <input type="text" name="ubicacion" value="{{ old('ubicacion') }}" class="form-control">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit"><i class="bi bi-check2 me-1"></i>Guardar</button>
    <a href="{{ route('aulas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection
