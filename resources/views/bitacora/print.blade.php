<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Bitácora</title>
  <style>
    body{ font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:12px; }
    table{ width:100%; border-collapse: collapse; }
    th,td{ border:1px solid #ddd; padding:6px; }
    th{ background:#f3f4f6; }
    h3{ margin:0 0 10px; }
    .muted{ color:#6b7280; font-size:11px; }
  </style>
</head>
<body>
  <h3>Bitácora del Sistema</h3>
  <div class="muted">Rango: {{ $desde ?? '-' }} a {{ $hasta ?? '-' }}</div>
  <table>
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Usuario</th>
        <th>Acción</th>
        <th>Tabla</th>
        <th>ID</th>
        <th>IP</th>
        <th>Descripción</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ $r->fecha }}</td>
          <td>{{ $r->usuario ?? '-' }}</td>
          <td>{{ $r->accion }}</td>
          <td>{{ $r->tabla_afectada }}</td>
          <td>{{ $r->id_afectado }}</td>
          <td>{{ $r->ip_origen }}</td>
          <td>{{ $r->descripcion }}</td>
        </tr>
      @empty
        <tr><td colspan="7">Sin registros.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>

