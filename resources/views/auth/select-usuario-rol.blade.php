@extends('layouts.auth')

@section('content')
<div class="card card-auth p-4">
  <div class="text-center mb-3">
    <h5 class="fw-semibold mb-1">Selecciona tu rol</h5>
    <small class="text-muted">Elige tu cargo dentro del sistema</small>
  </div>
  <div class="d-grid gap-3">
    <a class="btn btn-select" href="{{ route('login', ['perfil' => 'usuario', 'rol' => 'decano']) }}">🏛️ Decano</a>
    <a class="btn btn-select" href="{{ route('login', ['perfil' => 'usuario', 'rol' => 'administrador']) }}">🛠️ Administrador</a>
    <a class="btn btn-select" href="{{ route('login', ['perfil' => 'usuario', 'rol' => 'director']) }}">🎓 Director de Carrera</a>
  </div>
  <div class="text-center mt-3"><a href="{{ route('login.select') }}" class="link-muted">← Volver</a></div>
</div>
@endsection

