@php($title = 'Editar Asistencia')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Editar Asistencia</h4>
    <small class="text-muted">Ajusta el estado y justificación</small>
  </div>
</div>

<div class="mb-3">
  <div class="p-3 bg-light rounded border">
    <div><strong>Docente:</strong> {{ $asistencia->docente->usuario->nombre ?? '' }} {{ $asistencia->docente->usuario->apellido ?? '' }}</div>
    <div class="mt-1"><strong>Materia/Grupo/Gestión:</strong> {{ $asistencia->horario->grupo->materia->nombre ?? '' }} — Grupo {{ $asistencia->horario->grupo->nombre_grupo ?? '' }} / {{ $asistencia->horario->grupo->gestion->codigo ?? '' }}</div>
    <div class="mt-1"><strong>Fecha:</strong> {{ $asistencia->fecha }} — <strong>Entrada:</strong> {{ $asistencia->hora_entrada }}</div>
  </div>
</div>

<form method="POST" action="{{ route('asistencias.update', $asistencia) }}" class="row g-3">
  @csrf
  @method('PUT')
  <div class="col-md-4">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select" required>
      @foreach(['PRESENTE','AUSENTE','RETRASO','JUSTIFICADO'] as $e)
        <option value="{{ $e }}" @selected(old('estado', $asistencia->estado)===$e)>{{ ucfirst(strtolower($e)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-8">
    <label class="form-label">Justificación</label>
    <textarea name="justificacion" class="form-control" rows="3" placeholder="Ingrese la justificación si es necesario...">{{ old('justificacion', $asistencia->justificacion) }}</textarea>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit">Actualizar</button>
    <a href="{{ route('asistencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

