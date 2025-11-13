@php($title = 'Roles')
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Roles</h4>
    <small class="text-muted">Gestión de roles</small>
  </div>
  <div>
    <a href="{{ route('roles.create') }}" class="btn btn-teal"><i class="bi bi-plus-lg me-1"></i>Nuevo Rol</a>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th style="width:120px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($roles as $r)
          <tr>
            <td>{{ $r->id_rol }}</td>
            <td>{{ $r->nombre }}</td>
            <td>{{ $r->descripcion }}</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('roles.edit',$r->id_rol) }}"><i class="bi bi-pencil"></i></a>
              <form action="{{ route('roles.destroy',$r->id_rol) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar rol?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-muted">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">{{ $roles->links('vendor.pagination.teal') }}</div>
</div>
@endsection

