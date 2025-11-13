@php($title = $title ?? 'Consulta de Horarios')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Consulta de Horarios</h4>
    <small class="text-muted">Filtra por docente, grupo o aula. Vista semanal.</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('consultas.horarios.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Exportar CSV</a>
    <button class="btn btn-teal" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
  </div>
</div>

<!-- Botones en móvil -->
<div class="d-lg-none mb-2 d-flex gap-2">
  <a href="{{ route('consultas.horarios.export', request()->query()) }}" class="btn btn-outline-secondary w-50"><i class="bi bi-download me-1"></i>CSV</a>
  <button class="btn btn-teal w-50" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
  </div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end m-0">
      <div class="col-lg-4">
        <label class="form-label">Docente</label>
        <select name="docente_id" class="form-select">
          <option value="">Todos</option>
          @foreach($docentes as $d)
            <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>
              {{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-4">
        <label class="form-label">Grupo</label>
        <select name="grupo_id" class="form-select">
          <option value="">Todos</option>
          @foreach($grupos as $g)
            <option value="{{ $g->id_grupo }}" @selected(($grupoId ?? null)==$g->id_grupo)>
              {{ $g->materia->nombre ?? '' }} ({{ $g->gestion->codigo ?? '' }}) — Grupo {{ $g->nombre_grupo }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3">
        <label class="form-label">Aula</label>
        <select name="aula_id" class="form-select">
          <option value="">Todas</option>
          @foreach($aulas as $a)
            <option value="{{ $a->id_aula }}" @selected(($aulaId ?? null)==$a->id_aula)>{{ $a->nombre }} @if($a->codigo) ({{ $a->codigo }}) @endif</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-1 d-grid">
        <button class="btn btn-teal" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

@if(collect($porDia)->flatten()->count() === 0)
  <div class="alert alert-info">No hay horarios para los filtros seleccionados.</div>
@else
  @php($dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'])
  <style>
    @media print { .topbar, .sidebar, .btn, form, .toast-container { display:none !important; } body { background:#fff; } .print-wrap { margin:0; } }
    .grid-table th, .grid-table td { vertical-align: top; }
    .slot-cell { min-width: 160px; }
    .event { background:#e6fffb;border:1px solid #b2f5ea;border-radius:8px;padding:6px 8px;margin-bottom:6px; }
    .event .title { font-weight:600; }
    .event small { color:#6c757d; }
  </style>

  <div class="table-responsive print-wrap">
    <table class="table table-bordered grid-table align-top">
      <thead class="table-light">
        <tr>
          <th style="width:100px">Hora</th>
          @foreach($dias as $d)
            <th class="text-center">{{ $d }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($slots as $t)
          <tr>
            <td class="text-nowrap"><span class="text-muted">{{ $t }}</span></td>
            @foreach($dias as $d)
              <td class="slot-cell">
                @php($items = collect($porDia[$d] ?? [])->filter(fn($h)=> substr($h->hora_inicio,0,5) === $t))
                @foreach($items as $h)
                  <div class="event">
                    <div class="title text-uppercase">{{ $h->grupo->materia->nombre ?? '' }}</div>
                    <small>
                      Grupo {{ $h->grupo->nombre_grupo ?? '' }} / {{ $h->grupo->gestion->codigo ?? '' }}<br>
                      {{ substr($h->hora_inicio,0,5) }}–{{ substr($h->hora_fin,0,5) }}
                      @if($h->aula)
                        · Aula: {{ $h->aula->nombre }}
                      @endif
                      @php($doc = optional(optional($h->docenteMateriaGestion)->docente)->usuario)
                      @if($doc)
                        <br>Doc.: {{ $doc->nombre }} {{ $doc->apellido }}
                      @endif
                    </small>
                  </div>
                @endforeach
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection


