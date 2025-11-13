@php($title = 'Importaciones')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Historial de Importaciones</h4>
    <small class="text-muted">Últimas cargas de datos masivos</small>
  </div>
  <div>
    <a href="{{ route('importaciones.create') }}" class="btn btn-teal"><i class="bi bi-upload me-1"></i>Nueva importación</a>
  </div>
  </div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Archivo</th>
          <th>Fecha</th>
          <th>Usuario</th>
          <th class="text-center">Progreso</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        @forelse($imports as $imp)
          <tr>
            <td>#{{ $imp->id_importacion }}</td>
            <td>{{ $imp->archivo_nombre }}</td>
            <td>{{ optional($imp->fecha)->format('Y-m-d H:i') }}</td>
            <td>{{ $imp->usuario_ejecutor }}</td>
            <td class="text-center">{{ (int)$imp->filas_procesadas }} / {{ (int)$imp->total_filas }}</td>
            <td>
              @php($st = $imp->estado)
              @if($st==='COMPLETADO')
                <span class="badge bg-success">COMPLETADO</span>
              @elseif($st==='PROCESANDO')
                <span class="badge bg-warning text-dark">PROCESANDO</span>
              @elseif($st==='ERROR')
                <span class="badge bg-danger">ERROR</span>
              @else
                <span class="badge bg-secondary">{{ $st }}</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-muted">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">{{ $imports->links('vendor.pagination.teal') }}</div>
</div>
@endsection

