@php($title = 'Carga Horaria')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Editar Carga Horaria</h4>
    <small class="text-muted">Actualiza docente, grupo, aula y horario</small>
  </div>
</div>

<form method="POST" action="{{ route('carga.update', $horario) }}" class="row g-3">
  @csrf
  @method('PUT')
  <div class="col-md-6">
    <label class="form-label">Docente</label>
    <select name="id_docente" class="form-select" required>
      @foreach($docentes as $d)
        <option value="{{ $d->id_docente }}" @selected(old('id_docente', $horario->docenteMateriaGestion->docente->id_docente ?? null)==$d->id_docente)>
          {{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }} - {{ $d->usuario->correo ?? '' }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Grupo (Materia / Gestión)</label>
    <select name="id_grupo" class="form-select" required>
      @foreach($grupos as $g)
        <option value="{{ $g->id_grupo }}" @selected(old('id_grupo', $horario->id_grupo)==$g->id_grupo)>
          {{ $g->materia->nombre ?? '' }} @if($g->materia?->codigo) ({{ $g->materia->codigo }}) @endif - {{ $g->gestion->codigo ?? '' }} - Grupo {{ $g->nombre_grupo }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Aula</label>
    <select name="id_aula" class="form-select">
      <option value="">Sin aula</option>
      @foreach($aulas as $a)
        <option value="{{ $a->id_aula }}" @selected(old('id_aula', $horario->id_aula)==$a->id_aula)>
          {{ $a->nombre }} @if($a->codigo) ({{ $a->codigo }}) @endif
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Día</label>
    <select name="dia" class="form-select" required>
      @foreach($dias as $d)
        <option value="{{ $d }}" @selected(old('dia', $horario->dia)==$d)>{{ $d }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Inicio</label>
    <input type="time" name="hora_inicio" value="{{ old('hora_inicio', substr($horario->hora_inicio,0,5)) }}" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Fin</label>
    <input type="time" name="hora_fin" value="{{ old('hora_fin', substr($horario->hora_fin,0,5)) }}" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Modalidad</label>
    <select name="modalidad" class="form-select" required>
      @foreach($modalidades as $m)
        <option value="{{ $m }}" @selected(old('modalidad', $horario->modalidad)==$m)>{{ $m }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Plataforma (si aplica)</label>
    <input type="text" name="virtual_plataforma" value="{{ old('virtual_plataforma', $horario->virtual_plataforma) }}" class="form-control">
  </div>
  <div class="col-md-8">
    <label class="form-label">Enlace virtual (si aplica)</label>
    <input type="text" name="virtual_enlace" value="{{ old('virtual_enlace', $horario->virtual_enlace) }}" class="form-control">
  </div>
  <div class="col-12">
    <label class="form-label">Observación</label>
    <textarea name="observacion" class="form-control" rows="2">{{ old('observacion', $horario->observacion) }}</textarea>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-teal" type="submit">Actualizar</button>
    <a href="{{ route('carga.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
@endsection

