@php($title = 'Docentes')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Nuevo Docente</h4>
    <small class="text-muted">Crea un registro de docente y su usuario</small>
  </div>
</div>

<form method="POST" action="{{ route('docentes.store') }}" class="row g-3">
  @csrf
  <div class="col-md-6">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Apellido</label>
    <input type="text" name="apellido" value="{{ old('apellido') }}" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Correo institucional</label>
    <input type="email" name="correo" value="{{ old('correo') }}" class="form-control" placeholder="nombre.apellido@ficct.edu.bo" required>
    <div class="form-text">Se creará un usuario activo y se asignará el rol DOCENTE.</div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Contraseña</label>
    <input type="password" name="contrasena" class="form-control" minlength="6" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Código Docente</label>
    <input type="text" name="codigo_docente" value="{{ old('codigo_docente') }}" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Profesión</label>
    <input type="text" name="profesion" value="{{ old('profesion') }}" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Grado Académico</label>
    <input type="text" name="grado_academico" value="{{ old('grado_academico') }}" class="form-control">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Guardar</button>
    <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

