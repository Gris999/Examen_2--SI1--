@php($title = 'Reportes y Analítica')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Reportes y Analítica</h4>
    <small class="text-muted">KPIs, gráficas y exportación</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('reportes.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Excel (CSV)</a>
    <a href="{{ route('reportes.print', request()->query()) }}" target="_blank" class="btn btn-teal"><i class="bi bi-printer me-1"></i>Imprimir / PDF</a>
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
        <select name="docente_id" class="form-select">
          <option value="">Todos</option>
          @foreach($docentes as $d)
            <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>{{ $d->usuario->nombre }} {{ $d->usuario->apellido }}</option>
          @endforeach
        </select>
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
      <div class="col-md-2 d-grid">
        <button class="btn btn-teal" type="submit">Aplicar</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Asistencia</div>
        <div class="display-6 fw-semibold">{{ $asistenciaPct }}%</div>
        <small class="text-muted">{{ $presentes }} de {{ $total }} registros</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Retrasos</div>
        <div class="display-6 fw-semibold">{{ $retrasos }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Ausencias</div>
        <div class="display-6 fw-semibold">{{ $ausentes }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Justificados</div>
        <div class="display-6 fw-semibold">{{ $justificados }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h6 class="mb-3">Distribución por estado</h6>
        <canvas id="chartEstados" height="130"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h6 class="mb-3">Operación</h6>
        <div class="d-flex justify-content-between"><span>Horarios activos</span><strong>{{ $horariosActivos }}</strong></div>
        <div class="d-flex justify-content-between"><span>Aulas disponibles</span><strong>{{ $aulasDisponibles }}</strong></div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <h6 class="mb-3">Top 5 retrasos por docente</h6>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          <tr><th>Docente</th><th class="text-end">Retrasos</th></tr>
        </thead>
        <tbody>
          @forelse($topRetrasos as $t)
            @php($u = optional($t->docente->usuario ?? null))
            <tr>
              <td>{{ trim(($u->nombre ?? '').' '.($u->apellido ?? '')) }}</td>
              <td class="text-end">{{ $t->c }}</td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
 </div>

<div class="card mb-3">
  <div class="card-body">
    <h6 class="mb-3">Asistencia por gestión (apilado por estado)</h6>
    <canvas id="chartGestion" height="120"></canvas>
  </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function(){
    const s = @json($seriesEstados);
    const ctx = document.getElementById('chartEstados');
    if (ctx){
      new Chart(ctx, {
        type: 'bar',
        data: { labels: s.labels, datasets: [{ label:'Registros', data: s.data, backgroundColor: ['#22c55e','#f59e0b','#ef4444','#06b6d4'] }]},
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
      });
    }

    const glabels = @json($gestionLabels ?? []);
    const gdatasets = @json($gestionDatasets ?? []);
    const ctx2 = document.getElementById('chartGestion');
    if (ctx2 && glabels.length){
      new Chart(ctx2, {
        type: 'bar',
        data: { labels: glabels, datasets: gdatasets },
        options: {
          responsive:true,
          plugins:{ legend:{ display:true, position:'bottom' } },
          scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
        }
      });
    }
  })();
</script>
@endsection
