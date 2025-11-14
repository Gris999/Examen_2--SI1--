@php($title = 'Registrar Asistencia')
@extends('layouts.app')

@section('content')
@php($mode = request()->get('modo','manual'))
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Registrar Asistencia</h4>
    <small class="text-muted">Elija la forma de registrar: manual o por QR</small>
  </div>
  <div class="btn-group" role="group" aria-label="Modo de asistencia">
    <button type="button" class="btn btn-outline-secondary" data-att-mode="manual">Manual</button>
    <button type="button" class="btn btn-outline-secondary" data-att-mode="qr">QR</button>
  </div>
</div>

<form method="GET" action="{{ route('asistencias.create') }}" class="row g-2 mb-3">
  <div class="col-md-4">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" value="{{ $fecha }}" class="form-control" onchange="this.form.submit()">
  </div>
</form>

<div data-mode-section="manual">
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
</div>

<div data-mode-section="qr" class="d-none">
  <div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h6 class="mb-0">Registro por QR</h6>
          <small class="text-muted">Selecciona uno de tus horarios del día y genera el QR.</small>
        </div>
        <span class="badge bg-success-subtle text-success px-3 py-2">
          {{ $horariosHoy->count() }} horario{{ $horariosHoy->count() === 1 ? '' : 's' }} activos hoy
        </span>
      </div>
      @if($horariosHoy->isNotEmpty())
        <div class="row g-3">
          @foreach($horariosHoy as $horario)
            <div class="col-md-6">
              <div class="border rounded-3 p-3 h-100 d-flex justify-content-between align-items-start gap-3">
                <div>
                  <div class="fw-semibold">{{ $horario->grupo->materia->nombre ?? '' }} - {{ $horario->grupo->nombre_grupo ?? '' }}</div>
                  <div class="text-muted small">
                    {{ $horario->dia }} {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fin,0,5) }}
                  </div>
                </div>
                <a href="{{ route('asistencias.qr', $horario) }}" class="btn btn-outline-success btn-sm">QR</a>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="alert alert-info mb-0">
          No tienes horarios activos hoy. Usa el módulo de Horarios para generar tus sesiones aprobadas.
        </div>
      @endif
    </div>
  </div>
</div>

<hr>
<div class="text-muted">
  <strong>Registrar por QR</strong>
  <p class="mb-0">Alterna entre el modo manual y el modo QR usando los botones superiores.</p>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('[data-mode-section]');
    const buttons = document.querySelectorAll('[data-att-mode]');
    function setMode(mode) {
      sections.forEach(section => section.classList.toggle('d-none', section.dataset.modeSection !== mode));
      buttons.forEach(button => {
        if (button.dataset.attMode === mode) {
          button.classList.remove('btn-outline-secondary');
          button.classList.add('btn-secondary');
        } else {
          button.classList.remove('btn-secondary');
          button.classList.add('btn-outline-secondary');
        }
      });
    }
    buttons.forEach(button => {
      button.addEventListener('click', () => setMode(button.dataset.attMode));
    });
    setMode("{{ $mode === 'qr' ? 'qr' : 'manual' }}");
  });
  </script>
@endsection
