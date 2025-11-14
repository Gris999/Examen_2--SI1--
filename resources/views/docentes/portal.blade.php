@php($title = 'Portal Docente')
@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-lg-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="fw-semibold">Bienvenido, {{ $docente->usuario->nombre ?? '' }}</h5>
        <p class="text-muted small">Gestión académica para tu carga</p>
        <div class="list-group list-group-flush">
          <a href="#registro" class="list-group-item list-group-item-action">Registro de asistencia</a>
          <a href="#historial" class="list-group-item list-group-item-action">Historial</a>
        </div>
        <div class="mt-4">
          <a href="{{ route('asistencias.create') }}" class="btn btn-sm btn-teal w-100 mb-2">Registrar asistencia</a>
          <a href="{{ route('asistencias.index') }}" class="btn btn-outline-secondary w-100">Ver mis asistencias</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-9">
    <div class="card shadow-sm mb-3" id="horario">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="fw-semibold mb-1">Horario semanal</h5>
            <small class="text-muted">Tus clases activas organizadas por día</small>
          </div>
          <span class="badge bg-primary-subtle text-primary">{{ $horarios->count() }} clases</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Día</th>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Aula</th>
                <th>Hora</th>
              </tr>
            </thead>
            <tbody>
              @forelse($horarios as $horario)
                <tr>
                  <td>{{ $horario->dia }}</td>
                  <td>{{ $horario->grupo->materia->nombre ?? '' }}</td>
                  <td>{{ $horario->grupo->nombre_grupo ?? '' }}</td>
                  <td>{{ $horario->aula->nombre ?? '—' }}</td>
                  <td>{{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fin,0,5) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">No hay horarios asignados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card shadow-sm" id="registro">
          <div class="card-body">
            <h6 class="fw-semibold">Resumen de asistencia</h6>
            <p class="text-muted small">Total: {{ $total }} registros</p>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted">Presentes</div>
                <div class="fw-semibold">{{ $presentes }}</div>
              </div>
              <div>
                <div class="text-muted">Retrasos</div>
                <div class="fw-semibold">{{ $retrasos }}</div>
              </div>
              <div>
                <div class="text-muted">Ausencias</div>
                <div class="fw-semibold">{{ $ausentes }}</div>
              </div>
            </div>
            <div class="mt-3">
              <div class="progress" style="height:8px">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $presencia }}%;"></div>
              </div>
              <small class="text-muted">{{ $presencia }}% de asistencia</small>
            </div>
            <div class="mt-4">
              @if($primerHorarioHoy)
                <a href="{{ route('asistencias.qr', $primerHorarioHoy) }}" class="btn btn-outline-success btn-sm me-2">QR de hoy</a>
              @else
                <button class="btn btn-outline-success btn-sm me-2" disabled>QR de hoy</button>
              @endif
              <a href="{{ route('asistencias.create') }}" class="btn btn-teal btn-sm">Registrar manual</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm" id="historial">
          <div class="card-body">
            <h6 class="fw-semibold">Historial reciente</h6>
            @forelse($asistencias as $asistencia)
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                  <div class="fw-semibold">{{ $asistencia->horario->grupo->materia->nombre ?? '' }}</div>
                  <small class="text-muted">{{ $asistencia->fecha }} - {{ $asistencia->estado }}</small>
                </div>
                <span class="badge bg-{{ $asistencia->estado === 'PRESENTE' ? 'success' : ($asistencia->estado === 'AUSENTE' ? 'danger' : 'warning') }}">{{ $asistencia->estado }}</span>
              </div>
            @empty
              <div class="text-muted small">Sin registros aún.</div>
            @endforelse
            <div class="mt-3">
              <a href="{{ route('asistencias.index') }}" class="link-muted">Ver historial completo</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h6 class="fw-semibold mb-3">Horarios del día ({{ ucfirst(\Carbon\Carbon::parse($hoy)->locale('es')->isoFormat('dddd, D [de] MMMM') ) }})</h6>
        @if($horariosHoy->isEmpty())
          <p class="text-muted mb-0">No tienes clases programadas para hoy.</p>
        @else
          <div class="row g-3">
            @foreach($horariosHoy as $horario)
              <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                  <div class="text-uppercase fw-semibold">{{ $horario->grupo->materia->nombre ?? '' }}</div>
                  <p class="mb-1 text-muted small">Grupo {{ $horario->grupo->nombre_grupo }}</p>
                  <p class="mb-1 text-muted small">
                    {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fin,0,5) }} • Aula {{ $horario->aula->nombre ?? '-' }}
                  </p>
                  <a href="{{ route('asistencias.qr', $horario) }}" class="btn btn-outline-success btn-sm">Generar QR</a>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
