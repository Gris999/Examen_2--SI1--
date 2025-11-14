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
    .badge{ display:inline-block; padding:2px 6px; border-radius:4px; font-size:11px; color:#fff; }
    .bg-primary{ background:#0d6efd; }
    .bg-success{ background:#198754; }
    .bg-warning{ background:#ffc107; color:#111; }
    .bg-danger{ background:#dc3545; }
    .bg-info{ background:#0dcaf0; color:#111; }
    .bg-dark{ background:#212529; }
    .bg-secondary{ background:#6c757d; }
  </style>
</head>
<body>
  <h3>Bitácora del Sistema</h3>
  <div class="muted">Rango: {{ $desde ?? '-' }} a {{ $hasta ?? '-' }} @if(!empty($ip)) · IP: {{ $ip }} @endif</div>
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
          <td>
            @php
              $acc = strtoupper($r->accion ?? '');
              $cls = 'bg-secondary';
              if (str_contains($acc,'LOGIN') || str_contains($acc,'LOGOUT')) $cls = 'bg-primary';
              elseif (str_contains($acc,'CREAR') || $acc==='INSERT' || str_contains($acc,'ASIGNAR') || str_contains($acc,'APROBAR')) $cls = 'bg-success';
              elseif (str_contains($acc,'EDIT') || $acc==='UPDATE') $cls = 'bg-warning';
              elseif (str_contains($acc,'ELIM') || $acc==='DELETE' || str_contains($acc,'RECHAZAR')) $cls = 'bg-danger';
              elseif (str_contains($acc,'IMPORT')) $cls = 'bg-info';
              elseif (str_contains($acc,'RESET')) $cls = 'bg-dark';
            @endphp
            <span class="badge {{ $cls }}">{{ $r->accion }}</span>
          </td>
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
