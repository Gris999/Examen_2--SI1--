<?php

namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRoles();
        $q = trim((string)$request->get('q'));
        $rolId = $request->integer('rol');
        $estado = $request->get('estado'); // '1'|'0'|null

        $users = Usuario::when($q, function($qry) use ($q){
                $qry->where(function($w) use ($q){
                    $w->where('nombre','ilike',"%$q%")
                      ->orWhere('apellido','ilike',"%$q%")
                      ->orWhere('correo','ilike',"%$q%");
                });
            })
            ->when($estado !== null && $estado !== '', fn($qry)=>$qry->where('activo', $estado === '1'))
            ->when($rolId, function($qry) use ($rolId){
                $ids = DB::table('usuario_rol')->where('id_rol',$rolId)->pluck('id_usuario');
                $qry->whereIn('id_usuario', $ids);
            })
            ->orderBy('id_usuario','desc')
            ->paginate(20)->withQueryString();
        $roles = DB::table('roles')->orderBy('nombre')->get();
        return view('usuarios.index', compact('users','q','roles','rolId','estado'));
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

        // Roles (respetando restricciones por rol actual)
        $rolesIds = $data['roles'] ?? [];
        $rolesIds = $this->filterRolesForCurrent($rolesIds);
        $this->syncRoles($u->id_usuario, $rolesIds);

        $this->logBitacora('CREAR_USUARIO','usuarios', (string)$u->id_usuario, 'Usuario creado');

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

        $rolesIds = $data['roles'] ?? [];
        $rolesIds = $this->filterRolesForCurrent($rolesIds);
        $this->syncRoles($usuario->id_usuario, $rolesIds);
        $this->logBitacora('EDITAR_USUARIO','usuarios', (string)$usuario->id_usuario, 'Usuario actualizado');
        return redirect()->route('usuarios.index')->with('status','Usuario actualizado');
    }

    public function destroy(Usuario $usuario)
    {
        $this->authorizeRoles();
        $usuario->activo = false;
        $usuario->save();
        $this->logBitacora('DESACTIVAR_USUARIO','usuarios', (string)$usuario->id_usuario, 'Usuario desactivado');
        return redirect()->route('usuarios.index')->with('status','Usuario desactivado');
    }

    public function toggle(Usuario $usuario)
    {
        $this->authorizeRoles();
        $usuario->activo = ! (bool)$usuario->activo;
        $usuario->save();
        $this->logBitacora($usuario->activo ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO','usuarios',(string)$usuario->id_usuario, $usuario->activo ? 'Usuario activado' : 'Usuario desactivado');
        return back()->with('status', $usuario->activo ? 'Usuario activado' : 'Usuario desactivado');
    }

    public function resetPassword(Usuario $usuario)
    {
        $this->authorizeRoles();
        $temp = 'Tmp'.strtoupper(substr(bin2hex(random_bytes(4)),0,6)).'!';
        $usuario->contrasena = $temp; // el modelo ya maneja hashing si aplica
        $usuario->save();
        $this->logBitacora('RESET_PASSWORD','usuarios', (string)$usuario->id_usuario, 'Restablecimiento de contraseña por admin');
        return back()->with('status', 'Contraseña temporal: '.$temp);
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
        $ok = in_array('administrador',$roles) || in_array('admin',$roles) || in_array('director',$roles) || in_array('director de carrera',$roles) || in_array('coordinador',$roles);
        abort_unless($ok, 403);
    }

    private function filterRolesForCurrent(array $rolesIds): array
    {
        $u = auth()->user();
        $my = $u?->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray() ?? [];
        // Admin/Director: sin restricciones
        if (in_array('administrador',$my) || in_array('admin',$my) || in_array('director',$my) || in_array('director de carrera',$my)) {
            return $rolesIds;
        }
        // Coordinador: sólo puede asignar rol DOCENTE
        if (in_array('coordinador',$my)) {
            $docRol = \Illuminate\Support\Facades\DB::table('roles')->whereRaw('LOWER(nombre)=LOWER(?)',[ 'docente' ])->value('id_rol');
            if ($docRol) { return in_array($docRol, $rolesIds) ? [ $docRol ] : [ $docRol ]; }
            return [];
        }
        return [];
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
