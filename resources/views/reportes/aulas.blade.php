@php($title = 'Reporte de Aulas')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Reporte de Aulas</h4>
    <small class="text-muted">Disponibilidad por franja y aulas más usadas</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('reportes.aulas.xls', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel (XLS)</a>
    <a href="{{ route('reportes.aulas.csv', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>CSV</a>
    <a href="{{ route('reportes.aulas.pdf', request()->query()) }}" class="btn btn-teal"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end m-0">
      <div class="col-md-3">
        <label class="form-label">Día</label>
        <select name="dia" class="form-select">
          <option value="">—</option>
          @foreach($dias as $d)
            <option value="{{ $d }}" @selected(($dia ?? '')===$d)>{{ $d }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Hora inicio</label>
        <input type="time" name="hora_inicio" value="{{ $hora_inicio }}" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Hora fin</label>
        <input type="time" name="hora_fin" value="{{ $hora_fin }}" class="form-control">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-teal" type="submit">Consultar</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="mb-2">Aulas disponibles</h6>
        <ul class="m-0 ps-3">
          @forelse($disponibles as $a)
            <li>{{ $a->nombre }} <span class="text-muted">(cap. {{ $a->capacidad }})</span></li>
          @empty
            <li class="text-muted">Sin resultados o sin filtros</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="mb-2">Aulas más usadas</h6>
        <ul class="m-0 ps-3">
          @forelse($masUsadas as $u)
            @php($au = \App\Models\Aula::find($u->id_aula))
            <li>{{ $au?->nombre ?? '—' }} <span class="text-muted">({{ $u->usos }} usos)</span></li>
          @empty
            <li class="text-muted">Sin datos</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
