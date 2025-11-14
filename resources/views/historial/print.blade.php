<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historial de Asistencia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print { .no-print { display:none !important; } }
    body { padding: 16px; font-size: 12px; }
    h5 { margin-bottom: 4px; }
    .summary { color:#555; margin-bottom: 12px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ced4da; padding: 6px; }
    thead th { background: #f8f9fa; font-weight: 600; }
    tbody tr:nth-child(even) { background: #fafafa; }
    .nowrap { white-space: nowrap; }
    .badge { padding: 2px 6px; border-radius: 4px; font-size: 11px; }
    .bg-success { background:#16a34a !important; color:#fff; }
    .bg-danger{ background:#dc2626 !important; color:#fff; }
    .bg-warning{ background:#f59e0b !important; color:#000; }
    .bg-info{ background:#06b6d4 !important; color:#fff; }
  </style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <h5 class="mb-0">Historial de Asistencia</h5>
      <div class="summary">
        @php
          $total = is_countable($rows) ? count($rows) : 0;
          $docenteNom = isset($docenteId) && $docenteId ? (optional(optional(\App\Models\Docente::with('usuario')->find($docenteId))->usuario)->nombre.' '.optional(optional(\App\Models\Docente::find($docenteId))->usuario)->apellido) : 'Todos';
          $materiaNom = isset($materiaId) && $materiaId ? (\App\Models\Materia::find($materiaId)->nombre ?? '—') : 'Todas';
          $gestionCod = isset($gestionId) && $gestionId ? (\App\Models\Gestion::find($gestionId)->codigo ?? '—') : 'Todas';
          $filtros = [];
          if (!empty($docenteId)) $filtros[] = 'Docente: '.trim($docenteNom);
          if (!empty($materiaId)) $filtros[] = 'Materia: '.$materiaNom;
          if (!empty($gestionId)) $filtros[] = 'Gestión: '.$gestionCod;
          if (!empty($estado)) $filtros[] = 'Estado: '.$estado;
          if (!empty($desde) || !empty($hasta)) $filtros[] = 'Fechas: '.($desde ?? '—').' a '.($hasta ?? '—');
        @endphp
        Total de asistencias: {{ $total }} @if(count($filtros)) · Filtros: {{ implode(' · ', $filtros) }} @else · Sin filtros @endif
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm">
      <thead class="table-light">
        <tr>
          <th>Fecha</th>
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
        @foreach($rows as $a)
          <tr>
            <td class="nowrap">{{ $a->fecha }}</td>
            <td>{{ $a->docente->usuario->nombre ?? '' }} {{ $a->docente->usuario->apellido ?? '' }}</td>
            <td>
              <div class="fw-semibold text-uppercase">{{ $a->horario->grupo->materia->nombre ?? '' }}</div>
              <div class="text-muted small">Grupo {{ $a->horario->grupo->nombre_grupo ?? '' }} / {{ $a->horario->grupo->gestion->codigo ?? '' }}</div>
            </td>
            <td>{{ $a->horario->aula->nombre ?? '-' }}</td>
            @php($hi = $a->horario->hora_inicio ?? '')
            @php($hf = $a->horario->hora_fin ?? '')
            <td class="nowrap">{{ $hi ? substr($hi,0,5) : '' }} - {{ $hf ? substr($hf,0,5) : '' }}</td>
            <td class="nowrap">{{ $a->hora_entrada ?? '-' }}</td>
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
        @endforeach
      </tbody>
    </table>
  </div>
</body>
</html>
