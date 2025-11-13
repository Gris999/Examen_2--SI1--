@extends('layouts.auth')

@section('content')
<div class="card card-auth p-4">
  <div class="text-center mb-3">
    <h5 class="fw-semibold mb-1">Selecciona tu perfil</h5>
    <small class="text-muted">Elige cómo deseas acceder al sistema</small>
  </div>
  <div class="d-grid gap-3">
    <a class="btn btn-select" href="{{ route('login.select.usuario') }}">👤 Usuario</a>
    <a class="btn btn-select" href="{{ route('login', ['perfil' => 'docente']) }}">📚 Docente</a>
  </div>
</div>
@endsection

