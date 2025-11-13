@php($title = 'Registrar Asistencia')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Registrar Asistencia (Manual)</h4>
    <small class="text-muted">Seleccione fecha y horario para registrar</small>
  </div>
</div>

<form method="GET" action="{{ route('asistencias.create') }}" class="row g-2 mb-3">
  <div class="col-md-4">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" value="{{ $fecha }}" class="form-control" onchange="this.form.submit()">
  </div>
</form>

<form method="POST" action="{{ route('asistencias.store') }}" class="row g-3">
  @csrf
  <input type="hidden" name="fecha" value="{{ $fecha }}">
  <div class="col-md-12">
    <label class="form-label">Horario (Docente / Materia / Grupo / Gestión — Día — Hora — Aula)</label>
    <select name="id_horario" class="form-select" required>
      <option value="">Seleccione...</option>
      @foreach($horarios as $h)
        <option value="{{ $h->id_horario }}">
          {{ $h->docenteMateriaGestion->docente->usuario->nombre ?? '' }} {{ $h->docenteMateriaGestion->docente->usuario->apellido ?? '' }} —
          {{ $h->grupo->materia->nombre ?? '' }} ({{ $h->grupo->nombre_grupo ?? '' }} / {{ $h->grupo->gestion->codigo ?? '' }}) —
          {{ $h->dia }} {{ substr($h->hora_inicio,0,5) }}-{{ substr($h->hora_fin,0,5) }} — {{ $h->aula->nombre ?? '-' }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Método</label>
    <select name="metodo" class="form-select">
      @foreach(['FORM','MANUAL'] as $m)
        <option value="{{ $m }}">{{ $m }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-8">
    <label class="form-label">Justificación (opcional)</label>
    <input type="text" name="justificacion" class="form-control" placeholder="Motivo o comentario...">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit">Guardar</button>
    <a href="{{ route('asistencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>

<hr>
<div class="text-muted">
  <strong>Registrar por QR</strong>
  <p class="mb-0">Para generar un QR, diríjase a "Horarios" y entre al QR de un horario específico.</p>
  </div>
@endsection

