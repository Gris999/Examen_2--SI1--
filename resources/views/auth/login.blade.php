@php($title = 'Iniciar sesión')
@extends('layouts.auth')

@section('content')
<div class="card card-auth p-4">
  <div class="text-center mb-3">
    <h5 class="fw-semibold mb-1">¡Bienvenido!</h5>
    <small class="text-muted">{{ $contextTitle ?? 'Inicia sesión para continuar' }}</small>
  </div>

  <form method="POST" action="{{ route('login.store') }}" class="mt-2">
    @csrf
    <div class="mb-3">
      <label class="form-label">Correo electrónico</label>
      <div class="input-group">
        <span class="input-group-text">📧</span>
        <input type="email" name="correo" value="{{ old('correo', old('login')) }}" class="form-control" placeholder="tu.correo@ficct.edu.bo" pattern="^[^@\s]+@ficct\.edu\.bo$" title="Debe ser un correo @ficct.edu.bo" required autofocus>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Contraseña</label>
      <div class="input-group">
        <span class="input-group-text">🔒</span>
        <input type="password" name="contrasena" class="form-control" required>
      </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="{{ route('password.request') }}" class="link-muted">¿Olvidaste tu contraseña?</a>
    </div>
    <button class="btn btn-teal w-100" type="submit">Iniciar Sesión</button>
  </form>

  <div class="text-center mt-3">
    <a href="{{ route('login.select') }}" class="link-muted">← Volver</a>
  </div>
</div>
@endsection
