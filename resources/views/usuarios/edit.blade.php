@php($title = 'Editar Usuario')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Editar Usuario</h4>
  <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="POST" action="{{ route('usuarios.update',$usuario->id_usuario) }}" class="row g-3">
      @csrf @method('PUT')
      <div class="col-md-4">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre',$usuario->nombre) }}" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Apellido</label>
        <input type="text" name="apellido" value="{{ old('apellido',$usuario->apellido) }}" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Correo</label>
        <input type="email" name="correo" value="{{ old('correo',$usuario->correo) }}" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nueva contraseña (opcional)</label>
        <input type="password" name="contrasena" class="form-control" placeholder="Dejar en blanco para mantener">
      </div>
      <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono',$usuario->telefono) }}" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-select">
          <option value="1" @selected(old('activo',$usuario->activo ? '1':'0')==='1')>Activo</option>
          <option value="0" @selected(old('activo',$usuario->activo ? '1':'0')==='0')>Inactivo</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Roles</label>
        <select name="roles[]" class="form-select" multiple size="5">
          @foreach($roles as $r)
            <option value="{{ $r->id_rol }}" @selected(in_array($r->id_rol,$userRoles))>{{ $r->nombre }}</option>
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

