@php($title = 'Reportes y Analítica')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Panel de Reportes</h4>
    <small class="text-muted">KPIs y analítica general @if($docenteOnly) · Vista de docente @endif</small>
  </div>
  <div class="d-none d-lg-flex gap-2">
    <a href="{{ route('reportes.asistencia') }}" class="btn btn-outline-secondary">Asistencia</a>
    <a href="{{ route('reportes.horarios') }}" class="btn btn-outline-secondary">Horarios</a>
    <a href="{{ route('reportes.aulas') }}" class="btn btn-outline-secondary">Aulas</a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Asistencia Global</div>
        <div class="fs-3 fw-semibold">{{ $porcAsistencia }}%</div>
      </div>
    </div>
  </div>
  <div class="col-md-9">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-1">Asistencia diaria (últimos 14 días)</h6>
        <canvas id="asisDia" height="70"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-2">Docentes con mayor carga</h6>
        <ul class="m-0 ps-3">
          @forelse($docentesCarga as $d)
            @php($doc = \App\Models\Docente::with('usuario')->find($d->id_docente))
            <li>{{ $doc?->usuario?->nombre }} {{ $doc?->usuario?->apellido }} <span class="text-muted">({{ $d->c }} horarios)</span></li>
          @empty
            <li class="text-muted">Sin datos</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-2">Aulas más usadas</h6>
        <ul class="m-0 ps-3">
          @forelse($aulasMasUsadas as $a)
            @php($au = \App\Models\Aula::find($a->id_aula))
            <li>{{ $au?->nombre ?? '—' }} <span class="text-muted">({{ $a->usos }} usos)</span></li>
          @empty
            <li class="text-muted">Sin datos</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-1">Puntualidad por docente (Top 5)</h6>
        <canvas id="puntualidadDoc" height="90"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-1">Ausentismo por materia (Top 5)</h6>
        <canvas id="ausentismoMat" height="90"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-1">Bloques horarios más demandados</h6>
        <canvas id="bloquesDem" height="70"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function(){
    const rows = @json($asisPorDia);
    const labels = rows.map(r => r.fecha);
    const data = rows.map(r => r.c);
    const ctx = document.getElementById('asisDia');
    if (ctx){
      new Chart(ctx, { type: 'line', data: { labels, datasets: [{ label: 'Asistencias', data, borderColor:'#0f766e', backgroundColor:'rgba(15,118,110,0.1)', tension:.2, fill:true }] }, options:{ responsive:true, plugins:{ legend:{ display:false } } } });
    }

    // Puntualidad top 5
    const punt = @json($puntualidadDocentes);
    const pLabels = punt.map(x => `${x.nombre} ${x.apellido}`);
    const pVals = punt.map(x => Number(x.pct));
    const ctxP = document.getElementById('puntualidadDoc');
    if (ctxP){ new Chart(ctxP, { type:'bar', data:{ labels:pLabels, datasets:[{ data:pVals, backgroundColor:'#22c55e' }] }, options:{ plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, suggestedMax:100, ticks:{ callback:(v)=>v+'%' } } } } }); }

    // Ausentismo por materia top 5
    const aus = @json($ausentismoMateriasTop);
    const aLabels = aus.map(x => x.nombre);
    const aVals = aus.map(x => x.aus);
    const ctxA = document.getElementById('ausentismoMat');
    if (ctxA){ new Chart(ctxA, { type:'bar', data:{ labels:aLabels, datasets:[{ data:aVals, backgroundColor:'#ef4444' }] }, options:{ plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } } }); }

    // Bloques demandados
    const bloq = @json($bloquesDemandados);
    const bLabels = bloq.map(x => x.bloque);
    const bVals = bloq.map(x => x.c);
    const ctxB = document.getElementById('bloquesDem');
    if (ctxB){ new Chart(ctxB, { type:'bar', data:{ labels:bLabels, datasets:[{ data:bVals, backgroundColor:'#0ea5e9' }] }, options:{ plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } } }); }
  })();
</script>
@endsection
