<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reporte de Horarios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding: 16px; font-size: 12px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ced4da; padding: 6px; }
    thead th { background: #f8f9fa; font-weight: 600; }
    tbody tr:nth-child(even) { background: #fafafa; }
    .nowrap { white-space: nowrap; }
  </style>
  </head>
<body>
  <h5 class="mb-2">Reporte de Horarios</h5>
  <table class="table table-sm">
    <thead class="table-light">
      <tr>
        <th>Docente</th>
        <th>Materia / Grupo / Gestión</th>
        <th>Día</th>
        <th>Bloque</th>
        <th>Aula</th>
        <th>Modalidad</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $h)
        @php($hi = $h->hora_inicio ?? '')
        @php($hf = $h->hora_fin ?? '')
        <tr>
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
      @endforeach
    </tbody>
  </table>
</body>
</html>

