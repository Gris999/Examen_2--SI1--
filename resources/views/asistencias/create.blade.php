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

@if(isset($horariosHoy) && $horariosHoy->isNotEmpty())
  <div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h6 class="mb-0">Horarios para generar QR</h6>
          <small class="text-muted">Escoge el horario del día y pulsa QR para registrar asistencia con tu móvil.</small>
        </div>
        <span class="badge bg-success-subtle text-success px-3 py-2">
          {{ $horariosHoy->count() }} horario{{ $horariosHoy->count() === 1 ? '' : 's' }} activos hoy
        </span>
      </div>
      <div class="row g-3">
        @foreach($horariosHoy as $horario)
          <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100 d-flex justify-content-between align-items-start gap-3">
              <div>
                <div class="fw-semibold">{{ $horario->grupo->materia->nombre ?? '' }} — {{ $horario->grupo->nombre_grupo ?? '' }}</div>
                <div class="text-muted small">
                  {{ $horario->dia }} {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fin,0,5) }}
                </div>
              </div>
              <a href="{{ route('asistencias.qr', $horario) }}" class="btn btn-outline-success btn-sm">QR</a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif

<hr>
<div class="text-muted">
  <strong>Registrar por QR</strong>
  <p class="mb-0">Para generar un QR, diríjase a "Horarios" y entre al QR de un horario específico.</p>
  </div>
@endsection
