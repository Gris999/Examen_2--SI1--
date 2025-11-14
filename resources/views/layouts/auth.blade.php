<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Sistema Académico' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(180deg,#e9fbf7,#f6fffd); min-height:100vh; }
    .brand-icon { width:74px;height:74px;border-radius:16px;background:#0f766e;display:flex;align-items:center;justify-content:center;color:#fff;font-size:36px; margin: 40px auto 12px; }
    .card-auth { max-width: 480px; margin: 0 auto; border:0; box-shadow: 0 8px 24px rgba(0,0,0,.08); border-radius:16px; }
    .btn-teal { background:#0f766e; color:#fff; border-radius:12px; }
    .btn-teal:hover { background:#0c5f59; color:#fff; }
    .btn-select { background:#0f766e; color:#fff; border-radius:14px; padding:.9rem 1rem; text-align:left; }
    .btn-select:hover { background:#0c5f59; color:#fff; }
    a.link-muted { color:#0f766e; text-decoration:none; }
    a.link-muted:hover { text-decoration:underline; }
    .toast-container { z-index: 1080; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row align-items-center min-vh-100">
      <div class="col-lg-6 d-none d-lg-block">
        <div class="px-4">
          <img src="https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/school.svg" alt="Ilustración" class="img-fluid" style="max-height: 360px;">
        </div>
      </div>
      <div class="col-lg-6">
        <div class="brand-icon">
          <span>🎓</span>
        </div>
        <div class="text-center mb-3">
          <h5 class="mb-0">Sistema Académico</h5>
          <small class="text-muted">Gestión Educativa Integral</small>
        </div>
    {{-- Toasts (status/warning/errors) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3">
      @if (session('status'))
        <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
          <div class="d-flex">
            <div class="toast-body">{{ session('status') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      @endif
      @if (session('warning'))
        <div class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
          <div class="d-flex">
            <div class="toast-body">{{ session('warning') }}</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      @endif
      @if ($errors->any())
        <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
          <div class="d-flex">
            <div class="toast-body">
              <strong>Ocurrieron errores:</strong>
              <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      @endif
    </div>

        {{ $slot ?? '' }}
        @yield('content')
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      var els = document.querySelectorAll('.toast');
      els.forEach(function(el){ new bootstrap.Toast(el).show(); });
    })();
  </script>
</body>
</html>
