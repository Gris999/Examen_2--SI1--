<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRoles();
        $q = trim((string)$request->get('q'));
        $users = Usuario::when($q, function($qry) use ($q){
                $qry->where(function($w) use ($q){
                    $w->where('nombre','ilike',"%$q%")
                      ->orWhere('apellido','ilike',"%$q%")
                      ->orWhere('correo','ilike',"%$q%");
                });
            })
            ->orderBy('id_usuario','desc')
            ->paginate(20)->withQueryString();
        $roles = DB::table('roles')->orderBy('nombre')->get();
        return view('usuarios.index', compact('users','q','roles'));
    }

    public function create()
    {
        $this->authorizeRoles();
        $roles = DB::table('roles')->orderBy('nombre')->get();
        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->authorizeRoles();
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'correo' => 'required|email|max:150|unique:usuarios,correo',
            'contrasena' => 'required|string|min:6',
            'telefono' => 'nullable|string|max:30',
            'activo' => 'nullable|boolean',
            'roles' => 'array',
            'roles.*' => 'integer',
        ]);

        $u = new Usuario();
        $u->nombre = $data['nombre'];
        $u->apellido = $data['apellido'] ?? null;
        $u->correo = $data['correo'];
        $u->contrasena = $data['contrasena'];
        $u->telefono = $data['telefono'] ?? null;
        $u->activo = (bool)($data['activo'] ?? true);
        $u->fecha_creacion = now();
        $u->save();

        // Roles
        $this->syncRoles($u->id_usuario, $data['roles'] ?? []);

        return redirect()->route('usuarios.index')->with('status','Usuario creado');
    }

    public function edit(Usuario $usuario)
    {
        $this->authorizeRoles();
        $roles = DB::table('roles')->orderBy('nombre')->get();
        $userRoles = DB::table('usuario_rol')->where('id_usuario',$usuario->id_usuario)->pluck('id_rol')->toArray();
        return view('usuarios.edit', compact('usuario','roles','userRoles'));
    }

    public function update(Request $request, Usuario $usuario)
    {
        $this->authorizeRoles();
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'correo' => 'required|email|max:150|unique:usuarios,correo,'.$usuario->id_usuario.',id_usuario',
            'contrasena' => 'nullable|string|min:6',
            'telefono' => 'nullable|string|max:30',
            'activo' => 'nullable|boolean',
            'roles' => 'array',
            'roles.*' => 'integer',
        ]);

        $usuario->nombre = $data['nombre'];
        $usuario->apellido = $data['apellido'] ?? null;
        $usuario->correo = $data['correo'];
        if (!empty($data['contrasena'])) { $usuario->contrasena = $data['contrasena']; }
        $usuario->telefono = $data['telefono'] ?? null;
        $usuario->activo = (bool)($data['activo'] ?? false);
        $usuario->save();

        $this->syncRoles($usuario->id_usuario, $data['roles'] ?? []);
        return redirect()->route('usuarios.index')->with('status','Usuario actualizado');
    }

    public function destroy(Usuario $usuario)
    {
        $this->authorizeRoles();
        DB::table('usuario_rol')->where('id_usuario',$usuario->id_usuario)->delete();
        $id = $usuario->id_usuario;
        $usuario->delete();
        return redirect()->route('usuarios.index')->with('status','Usuario eliminado');
    }

    private function syncRoles(int $userId, array $rolesIds): void
    {
        $rolesIds = array_values(array_unique(array_map('intval',$rolesIds)));
        DB::table('usuario_rol')->where('id_usuario',$userId)->delete();
        if (!empty($rolesIds)) {
            $rows = array_map(fn($rid)=>['id_usuario'=>$userId,'id_rol'=>$rid], $rolesIds);
            DB::table('usuario_rol')->insert($rows);
        }
        $this->logBitacora('UPDATE','usuario_rol', (string)$userId, 'Asignación de roles actualizada');
    }

    private function authorizeRoles(): void
    {
        $u = auth()->user();
        $roles = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles);
        abort_unless($ok, 403);
    }

    private function logBitacora(string $accion, string $tabla, string $id, string $descripcion): void
    {
        DB::table('bitacora')->insert([
            'id_usuario' => auth()->user()->id_usuario ?? null,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'id_afectado' => $id,
            'ip_origen' => request()->ip(),
            'descripcion' => $descripcion,
            'fecha' => now(),
        ]);
    }
}
