@php($title = 'Asignaciones (DMG)')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Nueva Asignación</h4>
    <small class="text-muted">Crea una asignación Docente–Materia–Gestión (queda PENDIENTE)</small>
  </div>
</div>

<form method="POST" action="{{ route('asignaciones.store') }}" class="row g-3">
  @csrf
  <div class="col-md-4">
    <label class="form-label">Docente</label>
    <select name="id_docente" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($docentes as $d)
        <option value="{{ $d->id_docente }}" @selected(old('id_docente')==$d->id_docente)>{{ $d->usuario->nombre }} {{ $d->usuario->apellido }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Materia</label>
    <select name="id_materia" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($materias as $m)
        <option value="{{ $m->id_materia }}" @selected(old('id_materia')==$m->id_materia)>{{ $m->nombre }} @if($m->codigo) ({{ $m->codigo }}) @endif</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Gestión</label>
    <select name="id_gestion" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($gestiones as $g)
        <option value="{{ $g->id_gestion }}" @selected(old('id_gestion')==$g->id_gestion)>{{ $g->codigo }} @if($g->activo) (Activa) @endif</option>
      @endforeach
    </select>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit">Asignar</button>
    <a href="{{ route('asignaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

