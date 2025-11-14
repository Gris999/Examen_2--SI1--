@php($title = 'Reporte de Asistencia')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Reporte de Asistencia</h4>
    <small class="text-muted">Filtra y exporta asistencias</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('reportes.asistencia.xls', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel (XLS)</a>
    <a href="{{ route('reportes.asistencia.pdf', request()->query()) }}" class="btn btn-teal"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end m-0">
      <div class="col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" name="desde" value="{{ $desde }}" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Docente</label>
        <select name="docente_id" class="form-select" @if($docenteOnly) disabled @endif>
          <option value="">Todos</option>
          @foreach($docentes as $d)
            <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>{{ $d->usuario->nombre }} {{ $d->usuario->apellido }}</option>
          @endforeach
        </select>
        @if($docenteOnly)
          <input type="hidden" name="docente_id" value="{{ $docenteId }}">
        @endif
      </div>
      <div class="col-md-3">
        <label class="form-label">Materia</label>
        <select name="materia_id" class="form-select">
          <option value="">Todas</option>
          @foreach($materias as $m)
            <option value="{{ $m->id_materia }}" @selected(($materiaId ?? null)==$m->id_materia)>{{ $m->nombre }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Gestión</label>
        <select name="gestion_id" class="form-select">
          <option value="">Todas</option>
          @foreach($gestiones as $g)
            <option value="{{ $g->id_gestion }}" @selected(($gestionId ?? null)==$g->id_gestion)>{{ $g->codigo }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
          <option value="">Todos</option>
          @foreach(['PRESENTE','AUSENTE','RETRASO','JUSTIFICADO'] as $e)
            <option value="{{ $e }}" @selected(($estado ?? '')===$e)>{{ ucfirst(strtolower($e)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="table-responsive">
  <table class="table align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:120px">Fecha</th>
        <th>Docente</th>
        <th>Materia / Grupo / Gestión</th>
        <th>Aula</th>
        <th>Bloque</th>
        <th>Entrada</th>
        <th>Método</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      @forelse($asistencias as $a)
        @php($hi = $a->horario->hora_inicio ?? '')
        @php($hf = $a->horario->hora_fin ?? '')
        <tr>
          <td>{{ $a->fecha }}</td>
          <td>{{ $a->docente->usuario->nombre ?? '' }} {{ $a->docente->usuario->apellido ?? '' }}</td>
          <td>
            <div class="fw-semibold text-uppercase">{{ $a->horario->grupo->materia->nombre ?? '' }}</div>
            <div class="text-muted small">Grupo {{ $a->horario->grupo->nombre_grupo ?? '' }} / {{ $a->horario->grupo->gestion->codigo ?? '' }}</div>
          </td>
          <td>{{ $a->horario->aula->nombre ?? '-' }}</td>
          <td class="nowrap">{{ $hi ? substr($hi,0,5) : '' }} - {{ $hf ? substr($hf,0,5) : '' }}</td>
          <td>{{ $a->hora_entrada ?? '-' }}</td>
          <td>{{ $a->metodo }}</td>
          <td>
            @php($st = $a->estado)
            @if($st==='PRESENTE')
              <span class="badge bg-success">PRESENTE</span>
            @elseif($st==='RETRASO')
              <span class="badge bg-warning text-dark">RETRASADO</span>
            @elseif($st==='AUSENTE')
              <span class="badge bg-danger">AUSENTE</span>
            @else
              <span class="badge bg-info text-dark">JUSTIFICADO</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-center text-muted">Sin registros</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $asistencias->links('vendor.pagination.teal') }}</div>
@endsection

