@php($title = 'Inicio')
@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row mb-3">
    <div class="col-12">
      <h4 class="mb-1">¡Bienvenido, {{ auth()->user()->nombre ?? auth()->user()->correo }}!</h4>
      <small class="text-muted">Panel de Control - Sistema Académico</small>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Docentes</div>
            <div class="h4 mb-0">{{ $counts['docentes'] ?? 0 }}</div>
          </div>
          <div class="badge bg-teal" style="background:#0f766e">👨‍🏫</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Materias</div>
            <div class="h4 mb-0">{{ $counts['materias'] ?? 0 }}</div>
          </div>
          <div class="badge bg-primary">📘</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Grupos</div>
            <div class="h4 mb-0">{{ $counts['grupos'] ?? 0 }}</div>
          </div>
          <div class="badge bg-purple" style="background:#6f42c1">🧮</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Aulas</div>
            <div class="h4 mb-0">{{ $counts['aulas'] ?? 0 }}</div>
          </div>
          <div class="badge bg-warning text-dark">🏫</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="row g-3">
        <div class="col-md-6">
          <a href="{{ route('aprobaciones.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">Aprobaciones</div>
                <small class="text-muted">{{ ($counts['pendientes'] ?? 0) }} solicitudes pendientes</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-6">
          <a href="{{ route('horarios.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">Horarios</div>
                <small class="text-muted">Gestión y consulta</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-6">
          <a href="{{ route('asistencias.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">Asistencia</div>
                <small class="text-muted">Registro manual y QR</small>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="fw-semibold mb-2">Pendientes de Aprobación</div>
          <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>Cargas horarias</div>
            <span class="badge bg-dark-subtle text-dark">{{ $counts['pendientes'] ?? 0 }}</span>
          </div>
          <a href="{{ route('aprobaciones.index') }}" class="btn btn-sm btn-outline-secondary">Ir a Aprobaciones</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
