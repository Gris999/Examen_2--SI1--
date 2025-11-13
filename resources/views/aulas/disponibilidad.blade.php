@php($title = 'Aulas — Disponibilidad')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Disponibilidad de Aulas</h4>
    <small class="text-muted">Filtra por día y rango horario para ver aulas libres</small>
  </div>
  <div>
    <a href="{{ route('aulas.index') }}" class="btn btn-outline-secondary">Volver</a>
  </div>
  </div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end m-0">
      <div class="col-md-2">
        <label class="form-label">Día</label>
        <select name="dia" class="form-select" required>
          <option value="">Seleccione...</option>
          @foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $k=>$v)
            <option value="{{ $k }}" @selected((string)$dia===(string)$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Hora inicio</label>
        <input type="time" name="hora_inicio" value="{{ $horaInicio }}" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Hora fin</label>
        <input type="time" name="hora_fin" value="{{ $horaFin }}" class="form-control" required>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-teal" type="submit">Consultar</button>
      </div>
    </form>
  </div>
</div>

@if($dia && $horaInicio && $horaFin)
  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Aulas libres</div>
        <div class="card-body">
          @php($items = $disponibles ?? collect())
          @if($items->isEmpty())
            <div class="alert alert-info m-0">No hay aulas libres en ese rango.</div>
          @else
            <ul class="list-group">
              @foreach($items as $a)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>
                    <b>{{ $a->codigo }}</b> — {{ $a->nombre }}
                    @if($a->ubicacion)
                      <small class="text-muted">({{ $a->ubicacion }})</small>
                    @endif
                  </span>
                  @if($a->capacidad)
                    <span class="badge bg-light border text-muted">{{ $a->capacidad }} plazas</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Aulas ocupadas</div>
        <div class="card-body">
          @php($occ = $aulas->whereIn('id_aula', $ocupadas ?? []))
          @if($occ->isEmpty())
            <div class="alert alert-success m-0">Todas las aulas están libres.</div>
          @else
            <ul class="list-group">
              @foreach($occ as $a)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>
                    <b>{{ $a->codigo }}</b> — {{ $a->nombre }}
                    @if($a->ubicacion)
                      <small class="text-muted">({{ $a->ubicacion }})</small>
                    @endif
                  </span>
                  @if($a->capacidad)
                    <span class="badge bg-warning text-dark">Ocupada</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>
@endif
@endsection

