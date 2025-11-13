@php($title = 'Restablecer contraseña')
@extends('layouts.auth')

@section('content')
<div class="card card-auth p-4">
  <div class="text-center mb-3">
    <h5 class="fw-semibold mb-1">Restablecer contraseña</h5>
    <small class="text-muted">Crea una nueva contraseña para tu cuenta</small>
  </div>

  <form method="POST" action="{{ route('password.update') }}" class="mt-2">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
      <label class="form-label">Correo</label>
      <div class="input-group">
        <span class="input-group-text">📧</span>
        <input type="email" name="correo" value="{{ old('correo', $email) }}" class="form-control" required>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Nueva contraseña</label>
      <div class="input-group">
        <span class="input-group-text">🔒</span>
        <input type="password" name="contrasena" class="form-control" required>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirmar contraseña</label>
      <div class="input-group">
        <span class="input-group-text">🔒</span>
        <input type="password" name="contrasena_confirmation" class="form-control" required>
      </div>
    </div>
    <button class="btn btn-teal w-100" type="submit">Actualizar contraseña</button>
  </form>

  <div class="text-center mt-3">
    <a href="{{ route('login.select') }}" class="link-muted">← Volver</a>
  </div>
</div>
@endsection
