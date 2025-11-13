@php($title = 'Editar Rol')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Editar Rol</h4>
  <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="POST" action="{{ route('roles.update',$rol->id_rol) }}" class="row g-3">
      @csrf @method('PUT')
      <div class="col-md-4">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre',$rol->nombre) }}" class="form-control" required>
      </div>
      <div class="col-md-8">
        <label class="form-label">Descripción</label>
        <input type="text" name="descripcion" value="{{ old('descripcion',$rol->descripcion) }}" class="form-control">
      </div>
      <div class="col-12">
        <button class="btn btn-teal"><i class="bi bi-save me-1"></i>Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection

