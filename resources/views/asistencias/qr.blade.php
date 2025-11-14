@php($title = 'QR de Asistencia')
@extends('layouts.app')

@section('content')
<h3 class="mb-3">QR de Asistencia — {{ $horario->grupo->materia->nombre ?? '' }} ({{ $horario->grupo->nombre_grupo ?? '' }} / {{ $horario->grupo->gestion->codigo ?? '' }})</h3>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body text-center">
        <p class="text-muted">Escanear para registrar asistencia (expira en 15 minutos).</p>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($signed) }}" alt="QR">
        <div class="mt-3 small">Fecha: {{ $fecha }} — Día: {{ $horario->dia }} — {{ substr($horario->hora_inicio,0,5) }}-{{ substr($horario->hora_fin,0,5) }}</div>
        <div class="mt-2"><span class="badge bg-light border text-muted">Zona horaria: {{ config('app.timezone') }} (Hora Bolivia)</span></div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div><b>Docente:</b> {{ $horario->docenteMateriaGestion->docente->usuario->nombre ?? '' }} {{ $horario->docenteMateriaGestion->docente->usuario->apellido ?? '' }}</div>
        <div><b>Aula:</b> {{ $horario->aula->nombre ?? '—' }}</div>
        <div class="mt-3">
          <a href="{{ $signed }}" class="btn btn-outline-primary btn-sm">Probar registro (debug)</a>
          <a href="{{ route('asistencias.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection


