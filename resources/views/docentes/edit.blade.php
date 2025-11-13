@php($title = 'Docentes')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Editar Docente</h4>
    <small class="text-muted">Actualiza la información del docente</small>
  </div>
</div>

<form method="POST" action="{{ route('docentes.update', $docente) }}" class="row g-3">
  @csrf
  @method('PUT')
  <div class="col-md-6">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre', $docente->usuario->nombre ?? '') }}" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Apellido</label>
    <input type="text" name="apellido" value="{{ old('apellido', $docente->usuario->apellido ?? '') }}" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Correo institucional</label>
    <input type="email" name="correo" value="{{ old('correo', $docente->usuario->correo ?? '') }}" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Código Docente</label>
    <input type="text" name="codigo_docente" value="{{ old('codigo_docente', $docente->codigo_docente) }}" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Profesión</label>
    <input type="text" name="profesion" value="{{ old('profesion', $docente->profesion) }}" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Grado Académico</label>
    <input type="text" name="grado_academico" value="{{ old('grado_academico', $docente->grado_academico) }}" class="form-control">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Actualizar</button>
    <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

