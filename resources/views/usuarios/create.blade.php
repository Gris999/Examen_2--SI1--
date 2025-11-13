@php($title = 'Nuevo Usuario')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Nuevo Usuario</h4>
  <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="POST" action="{{ route('usuarios.store') }}" class="row g-3">
      @csrf
      <div class="col-md-4">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Apellido</label>
        <input type="text" name="apellido" value="{{ old('apellido') }}" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Correo</label>
        <input type="email" name="correo" value="{{ old('correo') }}" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Contraseña</label>
        <input type="password" name="contrasena" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono') }}" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-select">
          <option value="1" @selected(old('activo','1')==='1')>Activo</option>
          <option value="0" @selected(old('activo')==='0')>Inactivo</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Roles</label>
        <select name="roles[]" class="form-select" multiple size="5">
          @foreach($roles as $r)
            <option value="{{ $r->id_rol }}">{{ $r->nombre }}</option>
          @endforeach
        </select>
        <small class="text-muted">Ctrl/Cmd + click para seleccionar múltiples.</small>
      </div>
      <div class="col-12">
        <button class="btn btn-teal"><i class="bi bi-save me-1"></i>Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection

