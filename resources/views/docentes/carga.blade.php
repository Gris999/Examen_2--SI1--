@php($title = 'Docentes')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Carga horaria de {{ $docente->usuario->nombre ?? '' }} {{ $docente->usuario->apellido ?? '' }}</h4>
    <small class="text-muted">Listado de asignaciones (materia, grupo, estado)</small>
  </div>
  <div>
    <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary">Volver</a>
  </div>
</div>

@if($asignaciones->isEmpty())
  <div class="alert alert-info">No tiene asignaciones registradas.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Materia</th>
          <th>Grupos</th>
          <th>Gestión</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($asignaciones as $i => $asg)
          @php($grupos = collect($asg->horarios)->map(fn($h)=>$h->grupo->nombre_grupo ?? null)->filter()->unique()->values())
          <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $asg->materia->nombre ?? '—' }}</td>
            <td>
              @if($grupos->isEmpty())
                —
              @else
                @foreach($grupos as $g)
                  <span class="badge bg-light border text-muted me-1">{{ $g }}</span>
                @endforeach
              @endif
            </td>
            <td>{{ $asg->gestion->nombre ?? ($asg->gestion->anio ?? '') }}</td>
            <td>
              @if(($asg->estado ?? '') === 'APROBADO')
                <span class="badge bg-success">Aprobado</span>
              @elseif(($asg->estado ?? '') === 'RECHAZADO')
                <span class="badge bg-danger">Rechazado</span>
              @else
                <span class="badge bg-secondary">{{ $asg->estado ?? 'PENDIENTE' }}</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

