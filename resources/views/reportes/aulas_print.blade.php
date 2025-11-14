<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reporte de Aulas</title>
  <style>
    body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 16px; font-size: 12px; }
    h5 { margin: 0 0 6px 0; }
    .muted { color: #555; margin-bottom: 12px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ced4da; padding: 6px; }
    thead th { background: #f8f9fa; font-weight: 600; }
    tbody tr:nth-child(even) { background: #fafafa; }
  </style>
</head>
<body>
  <h5>Reporte de Aulas</h5>
  <div class="muted">Día: {{ $dia ?? '—' }} · Rango: {{ $hora_inicio ?? '—' }} a {{ $hora_fin ?? '—' }}</div>

  <h6 style="margin: 8px 0 6px 0;">Aulas disponibles</h6>
  <table>
    <thead>
      <tr>
        <th>Aula</th>
        <th>Código</th>
        <th>Capacidad</th>
        <th>Ubicación</th>
      </tr>
    </thead>
    <tbody>
      @forelse($disponibles as $a)
        <tr>
          <td>{{ $a->nombre }}</td>
          <td>{{ $a->codigo ?? '' }}</td>
          <td>{{ $a->capacidad ?? '' }}</td>
          <td>{{ $a->ubicacion ?? '' }}</td>
        </tr>
      @empty
        <tr><td colspan="4">Sin resultados (aplica filtros)</td></tr>
      @endforelse
    </tbody>
  </table>

  <h6 style="margin: 16px 0 6px 0;">Aulas más usadas</h6>
  <table>
    <thead>
      <tr>
        <th>Aula</th>
        <th>Código</th>
        <th>Usos</th>
      </tr>
    </thead>
    <tbody>
      @forelse($masUsadas as $u)
        @php($au = \App\Models\Aula::find($u->id_aula))
        <tr>
          <td>{{ $au->nombre ?? '—' }}</td>
          <td>{{ $au->codigo ?? '' }}</td>
          <td>{{ $u->usos }}</td>
        </tr>
      @empty
        <tr><td colspan="3">Sin datos</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>

