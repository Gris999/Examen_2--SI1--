@php($title = 'Detalle de Bitácora')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Detalle de Bitácora #{{ $row->id_bitacora }}</h4>
  <a href="{{ route('bitacora.index') }}" class="btn btn-outline-secondary">Volver</a>
  </div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label text-muted">Fecha</label>
        <div class="form-control-plaintext">{{ $row->fecha }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label text-muted">Usuario</label>
        <div class="form-control-plaintext">{{ $row->usuario ?? '-' }} (ID: {{ $row->id_usuario ?? '-' }})</div>
      </div>
      <div class="col-md-4">
        <label class="form-label text-muted">Acción</label>
        <div class="form-control-plaintext"><span class="badge bg-secondary">{{ $row->accion }}</span></div>
      </div>
      <div class="col-md-4">
        <label class="form-label text-muted">Tabla afectada</label>
        <div class="form-control-plaintext">{{ $row->tabla_afectada }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label text-muted">ID afectado</label>
        <div class="form-control-plaintext">{{ $row->id_afectado }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label text-muted">IP Origen</label>
        <div class="form-control-plaintext">{{ $row->ip_origen }}</div>
      </div>
      <div class="col-12">
        <label class="form-label text-muted">Descripción</label>
        <div class="form-control" style="white-space: pre-wrap">{{ $row->descripcion }}</div>
      </div>
    </div>
  </div>
</div>
@endsection

