@extends('layouts.app')

@php($title = 'Horarios')

@section('content')
@php
  $heading = $heading ?? (isset($soloDocente) && $soloDocente ? 'Mis Horarios' : 'Horarios');
  $subheading = $subheading ?? (isset($soloDocente) && $soloDocente
    ? 'Visualiza únicamente tu carga aprobada. Mantén este módulo como referencia para generar QR y registrar asistencia.'
    : 'Crea y administra horarios aprobados');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">{{ $heading }}</h4>
    <small class="text-muted">{{ $subheading }}</small>
  </div>
  @unless(isset($soloDocente) && $soloDocente)
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#genAutoModal">Generar automáticos</button>
      <a href="{{ route('horarios.create') }}" class="btn btn-teal">Nuevo Horario</a>
    </div>
  @endunless
</div>

<!-- Botón de creación visible solo en pantallas pequeñas -->
@unless(isset($soloDocente) && $soloDocente)
  <div class="d-lg-none mb-2">
    <a href="{{ route('horarios.create') }}" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Nuevo Horario</a>
  </div>
@endunless

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    @unless(isset($soloDocente) && $soloDocente)
      <form method="GET" class="row g-2 align-items-center m-0">
        <div class="col-lg-3">
          <select name="docente_id" class="form-select">
            <option value="">Todos los docentes</option>
            @foreach($docentes as $d)
              <option value="{{ $d->id_docente }}" @selected(($docenteId ?? null)==$d->id_docente)>
                {{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-2">
          <select name="gestion_id" class="form-select">
            <option value="">Gestión</option>
            @foreach($gestiones as $g)
              <option value="{{ $g->id_gestion }}" @selected(($gestionId ?? null)==$g->id_gestion)>{{ $g->codigo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-2">
          <select name="materia_id" class="form-select">
            <option value="">Materia</option>
            @foreach($materias as $m)
              <option value="{{ $m->id_materia }}" @selected(($materiaId ?? null)==$m->id_materia)>{{ $m->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-2">
          <select name="grupo_id" class="form-select">
            <option value="">Grupo</option>
            @foreach($grupos as $gr)
              <option value="{{ $gr->id_grupo }}" @selected(($grupoId ?? null)==$gr->id_grupo)>{{ $gr->nombre_grupo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-2">
          <select name="aula_id" class="form-select">
            <option value="">Aula</option>
            @foreach($aulas as $a)
              <option value="{{ $a->id_aula }}" @selected(($aulaId ?? null)==$a->id_aula)>{{ $a->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-1">
          <select name="dia" class="form-select">
            <option value="">Día</option>
            @foreach($dias as $d)
              <option value="{{ $d }}" @selected(($dia ?? '')===$d)>{{ $d }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-2 d-grid mt-2">
          <button class="btn btn-teal" type="submit">Filtrar</button>
        </div>
      </form>
    @else
      <div class="alert alert-info mb-0">
        Solo se muestran tus horarios de la carga vigente. Usa “Registrar Asistencia” o el botón QR para crear tu registro diario.
      </div>
    @endunless
  </div>
</div>

@if(isset($soloDocente) && $soloDocente && isset($horariosHoy) && $horariosHoy->isNotEmpty())
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="mb-1 fw-semibold">Horarios disponibles hoy</h6>
          <p class="text-muted small mb-0">Genera un QR desde aquí para registrar asistencia inmediata.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary px-3 py-2">
          {{ $horariosHoy->count() }} horario{{ $horariosHoy->count() === 1 ? '' : 's' }}
        </span>
      </div>
      <div class="row g-3">
        @foreach($horariosHoy as $horario)
          <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
              <div>
                <div class="fw-semibold text-uppercase">{{ $horario->grupo->materia->nombre ?? '' }}</div>
                <div class="text-muted small mb-1">Grupo {{ $horario->grupo->nombre_grupo ?? '' }} / {{ $horario->grupo->gestion->codigo ?? '' }}</div>
                <div class="small text-muted">{{ $horario->dia }} • {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fin,0,5) }}</div>
                <div class="small text-muted">Aula {{ $horario->aula->nombre ?? '—' }}</div>
              </div>
              <a href="{{ route('asistencias.qr', $horario) }}" class="btn btn-outline-success btn-sm mt-3 align-self-start">Generar QR</a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif

@if ($horarios->count() === 0)
  <div class="alert alert-info">No hay horarios registrados.</div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Docente</th>
          <th>Materia / Grupo / Gestión</th>
          <th>Día</th>
          <th>Hora</th>
          <th>Aula</th>
          <th>Modalidad</th>
          <th class="text-end" style="width:200px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($horarios as $h)
          <tr>
            <td>{{ $horarios->firstItem() + $loop->index }}</td>
            <td>{{ $h->docenteMateriaGestion->docente->usuario->nombre ?? '' }} {{ $h->docenteMateriaGestion->docente->usuario->apellido ?? '' }}</td>
            <td>
              <div class="fw-semibold text-uppercase">{{ $h->grupo->materia->nombre ?? '' }}</div>
              <div class="text-muted small">Grupo {{ $h->grupo->nombre_grupo ?? '' }} / {{ $h->grupo->gestion->codigo ?? '' }}</div>
            </td>
            <td>{{ $h->dia }}</td>
            <td>{{ substr($h->hora_inicio,0,5) }} - {{ substr($h->hora_fin,0,5) }}</td>
            <td>{{ $h->aula->nombre ?? '-' }}</td>
            <td>{{ $h->modalidad }}</td>
            <td class="text-end">
              @if(($h->estado ?? 'PENDIENTE') === 'APROBADA')
                <a href="{{ route('asistencias.qr', $h) }}" class="btn btn-sm btn-outline-success me-1">QR</a>
              @endif
              @unless(isset($soloDocente) && $soloDocente)
                <a href="{{ route('horarios.edit', $h) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                <form action="{{ route('horarios.destroy', $h) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar horario?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              @endunless
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div>{{ $horarios->links('vendor.pagination.teal') }}</div>
@endif

<!-- Modal: Generar automáticos -->
<div class="modal fade" id="genAutoModal" tabindex="-1" aria-labelledby="genAutoModalLbl" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="genAutoModalLbl">Generar horarios automáticos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('horarios.generar') }}" onsubmit="return validarGenAuto(this);">
        @csrf
        @if(!empty($gestionId))
          <input type="hidden" name="gestion_id" value="{{ $gestionId }}">
        @endif
        @if(!empty($docenteId))
          <input type="hidden" name="docente_id" value="{{ $docenteId }}">
        @endif
        @if(!empty($materiaId))
          <input type="hidden" name="materia_id" value="{{ $materiaId }}">
        @endif
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <div class="alert alert-info py-2">
                <div><strong>Resumen:</strong> asignaciones aprobadas según filtros: <b>{{ $aproCount ?? 0 }}</b></div>
                <div>Grupos sin horario asociados: <b>{{ $toProcess ?? 0 }}</b></div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Días a considerar</label>
              <div class="row row-cols-2 g-2">
                @foreach(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'] as $d)
                  <div class="col">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="dias[]" id="dia_{{ $d }}" value="{{ $d }}" checked>
                      <label class="form-check-label" for="dia_{{ $d }}">{{ $d }}</label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Bloques horarios</label>
              <div class="row row-cols-1 g-2">
                @foreach(['08:00-10:00','10:00-12:00','14:00-16:00','16:00-18:00'] as $s)
                  <div class="col">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="slots[]" id="slot_{{ str_replace(':','',str_replace('-','_',$s)) }}" value="{{ $s }}" checked>
                      <label class="form-check-label" for="slot_{{ str_replace(':','',str_replace('-','_',$s)) }}">{{ $s }}</label>
                    </div>
                  </div>
                @endforeach
              </div>
              <div class="form-text">Selecciona al menos un día y un bloque.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-teal">Generar</button>
        </div>
      </form>
    </div>
  </div>
 </div>

<script>
  function validarGenAuto(form){
    var dias = form.querySelectorAll('input[name="dias[]"]:checked');
    var slots = form.querySelectorAll('input[name="slots[]"]:checked');
    if (dias.length === 0 || slots.length === 0) {
      alert('Selecciona al menos un día y un bloque horario.');
      return false;
    }
    return true;
  }
</script>

@endsection
