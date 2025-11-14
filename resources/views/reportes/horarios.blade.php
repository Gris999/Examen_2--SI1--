@php($title = 'Reporte de Horarios')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Reporte de Horarios</h4>
    <small class="text-muted">Filtra por docente, gestión, materia, grupo, aula y día</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('reportes.horarios.pdf', request()->query()) }}" class="btn btn-teal"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
    <a href="{{ route('reportes.horarios.xls', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel (XLS)</a>
    <a href="{{ route('reportes.horarios.csv', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>CSV</a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end m-0">
      <div class="col-lg-3">
        <label class="form-label">Docente</label>
        <select name="docente_id" class="form-select">
          <option value="">Todos</option>
          @foreach($docentes as $d)
            <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>{{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <label class="form-label">Gestión</label>
        <select name="gestion_id" class="form-select">
          <option value="">Todas</option>
          @foreach($gestiones as $g)
            <option value="{{ $g->id_gestion }}" @selected(($gestionId ?? null)==$g->id_gestion)>{{ $g->codigo }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <label class="form-label">Materia</label>
        <select name="materia_id" class="form-select">
          <option value="">Todas</option>
          @foreach($materias as $m)
            <option value="{{ $m->id_materia }}" @selected(($materiaId ?? null)==$m->id_materia)>{{ $m->nombre }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <label class="form-label">Grupo</label>
        <select name="grupo_id" class="form-select">
          <option value="">Todos</option>
          @foreach($grupos as $gr)
            <option value="{{ $gr->id_grupo }}" @selected(($grupoId ?? null)==$gr->id_grupo)>{{ $gr->nombre_grupo }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2">
        <label class="form-label">Aula</label>
        <select name="aula_id" class="form-select">
          <option value="">Todas</option>
          @foreach($aulas as $a)
            <option value="{{ $a->id_aula }}" @selected(($aulaId ?? null)==$a->id_aula)>{{ $a->nombre }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-1">
        <label class="form-label">Día</label>
        <select name="dia" class="form-select">
          <option value="">—</option>
          @foreach($dias as $d)
            <option value="{{ $d }}" @selected(($dia ?? '')===$d)>{{ $d }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-lg-2 d-grid mt-2">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="table-responsive">
  <table class="table align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Docente</th>
        <th>Materia / Grupo / Gestión</th>
        <th>Día</th>
        <th>Bloque</th>
        <th>Aula</th>
        <th>Modalidad</th>
      </tr>
    </thead>
    <tbody>
      @forelse($horarios as $h)
        @php($hi = $h->hora_inicio ?? '')
        @php($hf = $h->hora_fin ?? '')
        <tr>
          <td>{{ $horarios->firstItem() + $loop->index }}</td>
          <td>{{ $h->docenteMateriaGestion->docente->usuario->nombre ?? '' }} {{ $h->docenteMateriaGestion->docente->usuario->apellido ?? '' }}</td>
          <td>
            <div class="fw-semibold text-uppercase">{{ $h->grupo->materia->nombre ?? '' }}</div>
            <div class="text-muted small">Grupo {{ $h->grupo->nombre_grupo ?? '' }} / {{ $h->grupo->gestion->codigo ?? '' }}</div>
          </td>
          <td>{{ $h->dia }}</td>
          <td class="nowrap">{{ $hi ? substr($hi,0,5) : '' }} - {{ $hf ? substr($hf,0,5) : '' }}</td>
          <td>{{ $h->aula->nombre ?? '-' }}</td>
          <td>{{ $h->modalidad }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center text-muted">Sin registros</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $horarios->links('vendor.pagination.teal') }}</div>
@endsection
