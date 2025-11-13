@php($title = 'Recuperar contraseña')
@extends('layouts.auth')

@section('content')
<div class="card card-auth p-4">
  <div class="text-center mb-3">
    <h5 class="fw-semibold mb-1">Recuperar contraseña</h5>
    <small class="text-muted">Ingresa tu correo para recibir el enlace</small>
  </div>

  <form method="POST" action="{{ route('password.email') }}" class="mt-2">
    @csrf
    <div class="mb-3">
      <label class="form-label">Correo electrónico</label>
      <div class="input-group">
        <span class="input-group-text">📧</span>
        <input type="email" name="correo" value="{{ old('correo', old('email')) }}" class="form-control" placeholder="tu.correo@ficct.edu.bo" pattern="^[^@\s]+@ficct\.edu\.bo$" title="Debe ser un correo @ficct.edu.bo" required>
      </div>
    </div>
    <button class="btn btn-teal w-100" type="submit">Enviar enlace</button>
  </form>

  @if (session('dev_link'))
    <div class="alert alert-info mt-3">
      <div><b>Modo desarrollo:</b> usa este enlace directo para resetear:</div>
      <div><a href="{{ session('dev_link') }}">{{ session('dev_link') }}</a></div>
    </div>
  @endif

  <div class="text-center mt-3">
    <a href="{{ route('login.select') }}" class="link-muted">← Volver</a>
  </div>
</div>
@endsection
