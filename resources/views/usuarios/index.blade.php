@php($title = 'Usuarios')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Usuarios</h4>
    <small class="text-muted">Gestión de usuarios y roles</small>
  </div>
  <div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-teal"><i class="bi bi-plus-lg me-1"></i>Nuevo Usuario</a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 m-0">
      <div class="col-md-4">
        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar (nombre, correo)">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-outline-secondary">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Teléfono</th>
          <th>Estado</th>
          <th>Roles</th>
          <th style="width:120px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $u)
          @php($asignados = DB::table('usuario_rol')->where('id_usuario',$u->id_usuario)->pluck('id_rol'))
          @php($nombres = $roles->whereIn('id_rol',$asignados)->pluck('nombre')->implode(', '))
          <tr>
            <td>{{ $u->id_usuario }}</td>
            <td>{{ $u->nombre }} {{ $u->apellido }}</td>
            <td>{{ $u->correo }}</td>
            <td>{{ $u->telefono ?? '-' }}</td>
            <td>{!! $u->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' !!}</td>
            <td>{{ $nombres }}</td>
            <td class="text-end">
              <a href="{{ route('usuarios.edit',$u->id_usuario) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <form action="{{ route('usuarios.destroy',$u->id_usuario) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar usuario?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">{{ $users->links('vendor.pagination.teal') }}</div>
</div>
@endsection

