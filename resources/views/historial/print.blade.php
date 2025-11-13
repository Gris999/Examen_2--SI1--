<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historial de Asistencia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print { .no-print { display:none !important; } }
    body { padding: 16px; }
  </style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="mb-0">Historial de Asistencia</h5>
      <small class="text-muted">Rango: {{ $desde ?? '-' }} a {{ $hasta ?? '-' }}</small>
    </div>
    <button class="btn btn-sm btn-primary no-print" onclick="window.print()">Imprimir</button>
  </div>

  <div class="table-responsive">
    <table class="table table-sm">
      <thead class="table-light">
        <tr>
          <th>Fecha</th>
          <th>Docente</th>
          <th>Materia / Grupo / Gestión</th>
          <th>Aula</th>
          <th>Entrada</th>
          <th>Método</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $a)
          <tr>
            <td>{{ $a->fecha }}</td>
            <td>{{ $a->docente->usuario->nombre ?? '' }} {{ $a->docente->usuario->apellido ?? '' }}</td>
            <td>
              <div class="fw-semibold text-uppercase">{{ $a->horario->grupo->materia->nombre ?? '' }}</div>
              <div class="text-muted small">Grupo {{ $a->horario->grupo->nombre_grupo ?? '' }} / {{ $a->horario->grupo->gestion->codigo ?? '' }}</div>
            </td>
            <td>{{ $a->horario->aula->nombre ?? '-' }}</td>
            <td>{{ $a->hora_entrada ?? '-' }}</td>
            <td>{{ $a->metodo }}</td>
            <td>{{ $a->estado }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</body>
</html>

