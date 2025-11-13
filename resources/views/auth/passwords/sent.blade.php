@php($title = 'Enlace enviado')
@extends('layouts.auth')

@section('content')
@php($raw = session('email'))
@php($masked = $raw ? (function($e){ $parts = explode('@',$e); $local = $parts[0] ?? ''; $domain = $parts[1] ?? ''; $localMasked = strlen($local)>1 ? substr($local,0,1).'***' : ($local?:'*'); return trim($localMasked.'@'.$domain,'@'); })($raw) : null)
<div class="card card-auth p-4 text-center">
  <div class="mb-2" style="font-size:42px">✅</div>
  <h5 class="fw-semibold mb-1">¡Revisa tu correo!</h5>
  <p class="text-muted mb-3">Si existe una cuenta con <b>{{ $masked }}</b>, recibirás un enlace para restablecer tu contraseña.</p>

  @if (session('dev_link'))
    <div class="alert alert-info text-start">
      <div class="fw-semibold">Modo desarrollo</div>
      <div><a href="{{ session('dev_link') }}">Abrir enlace de restablecimiento</a></div>
    </div>
  @endif

  <div class="d-grid gap-2">
    <a href="{{ route('login.select') }}" class="btn btn-teal">Volver al inicio de sesión</a>
  </div>
</div>
@endsection
