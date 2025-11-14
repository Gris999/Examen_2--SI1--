@php($title = 'Importar Datos')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Importar Datos Masivos</h4>
    <small class="text-muted">Docentes, Materias u Horarios desde Excel/CSV</small>
  </div>
  <div>
    <a href="{{ route('importaciones.index') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i>Historial</a>
  </div>
  </div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    @if($errors->any())
      <div class="alert alert-danger">
        <div class="fw-semibold">Se encontraron problemas con el archivo:</div>
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li style="white-space: pre-line">{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    @if(! $supportsExcel)
      <div class="alert alert-warning">
        Para procesar Excel/CSV se requiere el paquete <code>maatwebsite/excel</code> instalado en el servidor.
      </div>
    @endif
    <form method="POST" action="{{ route('importaciones.store') }}" enctype="multipart/form-data" class="row g-3">
      @csrf
      <div class="col-md-3">
        <label class="form-label">Tipo de datos</label>
        <select name="tipo" class="form-select" required>
          <option value="">Seleccione…</option>
          <option value="docentes" @selected(old('tipo')==='docentes')>Docentes</option>
          <option value="materias" @selected(old('tipo')==='materias')>Materias</option>
          <option value="horarios" @selected(old('tipo')==='horarios')>Horarios</option>
          <option value="todo" @selected(old('tipo')==='todo')>Importación total (multi-hoja)</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Archivo (Excel .xlsx/.xls o .csv)</label>
        <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls,.csv" required>
        <small class="text-muted">Peso máximo 5MB. La primera fila debe contener encabezados.</small>
      </div>
      <div class="col-md-3 d-grid align-items-end">
        <button class="btn btn-teal" type="submit"><i class="bi bi-upload me-1"></i>Importar</button>
      </div>
    </form>

    <hr>
    <div class="small text-muted">
      <strong>Encabezados sugeridos:</strong>
      <ul class="mb-0">
        <li><strong>Docentes:</strong> nombre, apellido, correo, telefono, codigo_docente, profesion, grado_academico</li>
        <li><strong>Materias:</strong> id_carrera, nombre, codigo, carga_horaria, descripcion</li>
        <li><strong>Horarios:</strong> id_docente_materia_gestion, id_grupo, id_aula (opcional), dia, hora_inicio, hora_fin, modalidad, virtual_plataforma, virtual_enlace, observacion</li>
      </ul>
      <div class="mt-2">
        <span class="me-2">Plantillas:</span>
        <a class="btn btn-sm btn-outline-secondary me-1" href="{{ asset('templates/docentes_template.csv') }}"><i class="bi bi-download me-1"></i>Docentes CSV</a>
        <a class="btn btn-sm btn-outline-secondary me-1" href="{{ asset('templates/materias_template.csv') }}"><i class="bi bi-download me-1"></i>Materias CSV</a>
        <a class="btn btn-sm btn-outline-secondary me-3" href="{{ asset('templates/horarios_template.csv') }}"><i class="bi bi-download me-1"></i>Horarios CSV</a>

        <a class="btn btn-sm btn-outline-primary me-1" href="{{ route('importaciones.template.xlsx','docentes') }}"><i class="bi bi-file-earmark-excel me-1"></i>Docentes XLSX</a>
        <a class="btn btn-sm btn-outline-primary me-1" href="{{ route('importaciones.template.xlsx','materias') }}"><i class="bi bi-file-earmark-excel me-1"></i>Materias XLSX</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('importaciones.template.xlsx','horarios') }}"><i class="bi bi-file-earmark-excel me-1"></i>Horarios XLSX</a>
      </div>
      <div class="mt-2">
        <a class="btn btn-sm btn-teal" href="{{ route('importaciones.template.master.xlsx') }}"><i class="bi bi-collection me-1"></i>Plantilla XLSX (Total)</a>
      </div>
      <div class="mt-2">
        <small class="text-muted">Para "Importación total" puedes usar un único Excel con varias hojas: "docentes", "materias" y "horarios" (encabezados iguales a los listados arriba). El sistema detecta cada hoja por sus columnas.</small>
      </div>
    </div>
  </div>
</div>
@endsection
