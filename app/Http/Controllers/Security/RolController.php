<?php

namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    private function authorizeRoles(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles);
        abort_unless($ok, 403);
    }

    public function index()
    {
        $this->authorizeRoles();
        $roles = DB::table('roles')->orderBy('id_rol','desc')->paginate(20);
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $this->authorizeRoles();
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $this->authorizeRoles();
        $data = $request->validate([
            'nombre' => 'required|string|max:50|unique:roles,nombre',
            'descripcion' => 'nullable|string',
        ]);
        $id = DB::table('roles')->insertGetId($data);
        $this->log('INSERT','roles', (string)$id, 'Rol creado');
        return redirect()->route('roles.index')->with('status','Rol creado');
    }

    public function edit(int $rol)
    {
        $this->authorizeRoles();
        $r = DB::table('roles')->where('id_rol',$rol)->firstOrFail();
        return view('roles.edit', ['rol'=>$r]);
    }

    public function update(Request $request, int $rol)
    {
        $this->authorizeRoles();
        $data = $request->validate([
            'nombre' => 'required|string|max:50|unique:roles,nombre,'.$rol.',id_rol',
            'descripcion' => 'nullable|string',
        ]);
        DB::table('roles')->where('id_rol',$rol)->update($data);
        $this->log('UPDATE','roles', (string)$rol, 'Rol actualizado');
        return redirect()->route('roles.index')->with('status','Rol actualizado');
    }

    public function destroy(int $rol)
    {
        $this->authorizeRoles();
        DB::table('usuario_rol')->where('id_rol',$rol)->delete();
        DB::table('roles')->where('id_rol',$rol)->delete();
        $this->log('DELETE','roles', (string)$rol, 'Rol eliminado');
        return redirect()->route('roles.index')->with('status','Rol eliminado');
    }

    private function log(string $accion, string $tabla, string $id, string $desc): void
    {
        DB::table('bitacora')->insert([
            'id_usuario' => auth()->user()->id_usuario ?? null,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'id_afectado' => $id,
            'ip_origen' => request()->ip(),
            'descripcion' => $desc,
            'fecha' => now(),
        ]);
    }
}

