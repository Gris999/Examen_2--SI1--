@php($title = 'Asignar Docentes a Grupo')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Docentes para {{ $grupo->nombre_grupo }} — {{ $grupo->materia->nombre }} / {{ $grupo->gestion->codigo }}</h3>
  <a href="{{ route('grupos.index') }}" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Asignados a Materia/Gestión</div>
      <div class="card-body">
        @if($asignados->isEmpty())
          <div class="alert alert-info mb-0">Aún no hay docentes asignados a esta materia/gestión.</div>
        @else
          <ul class="list-group">
            @foreach($asignados as $a)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  {{ $a->docente->usuario->nombre ?? '' }} {{ $a->docente->usuario->apellido ?? '' }}
                  <small class="text-muted">— {{ $a->docente->usuario->correo ?? '' }}</small>
                  <span class="badge bg-secondary ms-2">{{ $a->estado }}</span>
                </span>
                <form method="POST" action="{{ route('grupos.docentes.remove', [$grupo, $a]) }}" onsubmit="return confirm('¿Quitar esta asignación?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Quitar</button>
                </form>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Agregar Docente</div>
      <div class="card-body">
        @if(!empty($tieneAsignado) && $tieneAsignado)
          <div class="alert alert-warning">Este grupo ya tiene un docente asignado. Debe quitarlo para asignar otro.</div>
        @endif
        <form method="GET" class="mb-3">
          <div class="input-group">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre o correo...">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
          </div>
        </form>

        @if ($docentes->count() === 0)
          <div class="alert alert-info mb-0">No hay docentes para mostrar.</div>
        @else
          <div class="list-group">
            @foreach($docentes as $d)
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  {{ $d->usuario->nombre ?? '' }} {{ $d->usuario->apellido ?? '' }}
                  <small class="text-muted">- {{ $d->usuario->correo ?? '' }}</small>
                </div>
                <form method="POST" action="{{ route('grupos.docentes.add', $grupo) }}">
                  @csrf
                  <input type="hidden" name="id_docente" value="{{ $d->id_docente }}">
                  <button class="btn btn-sm btn-primary" @if(!empty($tieneAsignado) && $tieneAsignado) disabled @endif>Asignar</button>
                </form>
              </div>
            @endforeach
          </div>
          <div class="mt-2">{{ $docentes->links() }}</div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="alert alert-secondary mt-4">
  Nota: esta asignación crea registros en <code>docente_materia_gestion</code> para la materia y gestión del grupo.
  La relación con el grupo se establece en el CU8 (Horarios), donde cada horario referencia al docente-materia-gestión y al grupo.
</div>
@endsection
