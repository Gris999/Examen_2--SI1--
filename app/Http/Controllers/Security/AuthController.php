<?php

namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $perfil = $request->query('perfil'); // 'usuario' | 'docente'
        $rol = $request->query('rol');       // 'decano' | 'administrador' | 'director'

        $contextTitle = 'Iniciar sesión';
        if ($perfil === 'docente') {
            $contextTitle = 'Iniciar sesión como Docente';
        } elseif ($perfil === 'usuario') {
            $map = [
                'decano' => 'Decano',
                'administrador' => 'Administrador',
                'director' => 'Director de Carrera',
            ];
            if ($rol && isset($map[$rol])) {
                $contextTitle = 'Iniciar sesión como '.$map[$rol];
            } else {
                $contextTitle = 'Iniciar sesión como Usuario';
            }
        }

        return view('auth.login', [
            'contextTitle' => $contextTitle,
        ]);
    }

    public function login(Request $request)
    {
        // CU1: Nuevo flujo de login con validación de activo, rol y bitácora
        $correo = $request->input('correo', $request->input('login'));
        $contrasena = $request->input('contrasena', $request->input('password'));

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['correo' => $correo, 'contrasena' => $contrasena],
            [
                'correo' => ['required', 'email', 'regex:/@ficct\\.edu\\.bo$/i'],
                'contrasena' => ['required', 'string', 'min:6'],
            ],
            [
                'correo.required' => 'Ingrese su correo.',
                'correo.email' => 'Ingrese un correo válido.',
                'contrasena.required' => 'Ingrese su contraseña.',
                'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres.',
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput(['login' => $correo, 'correo' => $correo]);
        }

        // Buscar activo = TRUE en la tabla `usuarios`
        $user = \App\Models\Usuario::where('correo', $correo)->where('activo', true)->first();
        if (!$user) {
            try {
                \Illuminate\Support\Facades\DB::table('bitacora')->insert([
                    'id_usuario' => null,
                    'accion' => 'LOGIN_FAIL',
                    'tabla_afectada' => 'usuarios',
                    'id_afectado' => (string) $correo,
                    'ip_origen' => $request->ip(),
                    'descripcion' => 'Intento fallido (usuario no existe o inactivo)',
                    'fecha' => now(),
                ]);
            } catch (\Throwable $e) {}

            return back()
                ->withErrors(['login' => 'Credenciales inválidas o usuario inactivo'])
                ->withInput(['login' => $correo]);
        }

        $stored = (string) $user->contrasena;
        $isBcrypt = str_starts_with($stored, '$2y$');

        $passwordOk = false;
        if ($isBcrypt) {
            $passwordOk = \Illuminate\Support\Facades\Hash::check($contrasena, $stored);
        } else {
            // Compatibilidad con datos heredados (texto plano o MD5)
            $plainMatch = hash_equals($stored, (string) $contrasena);
            $md5Match = (strlen($stored) === 32 && preg_match('/^[a-f0-9]{32}$/i', $stored))
                ? hash_equals(md5((string) $contrasena), $stored)
                : false;

            if ($plainMatch || $md5Match) {
                // Rehash a bcrypt
                $user->contrasena = $contrasena; // mutator aplicará bcrypt
                $user->save();
                $passwordOk = true;
            }
        }

        if (! $passwordOk) {
            try {
                \Illuminate\Support\Facades\DB::table('bitacora')->insert([
                    'id_usuario' => $user->id_usuario ?? null,
                    'accion' => 'LOGIN_FAIL',
                    'tabla_afectada' => 'usuarios',
                    'id_afectado' => (string) $correo,
                    'ip_origen' => $request->ip(),
                    'descripcion' => 'Intento fallido (credenciales)',
                    'fecha' => now(),
                ]);
            } catch (\Throwable $e) {}

            return back()
                ->withErrors(['login' => 'Credenciales inválidas o usuario inactivo'])
                ->withInput(['login' => $correo]);
        }

        // Login y bitácora
        \Illuminate\Support\Facades\Auth::login($user);
        $request->session()->regenerate();
        try {
            \Illuminate\Support\Facades\DB::table('bitacora')->insert([
                'id_usuario' => $user->id_usuario,
                'accion' => 'LOGIN',
                'tabla_afectada' => 'usuarios',
                'id_afectado' => (string) $user->id_usuario,
                'ip_origen' => $request->ip(),
                'descripcion' => 'Inicio de sesión exitoso',
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {}

        // Redirección según rol
        $roles = $user->roles()->pluck('nombre')->map(fn($n) => mb_strtolower($n))->toArray();
        if (in_array('admin', $roles, true) || in_array('administrador', $roles, true)) {
            return redirect()->intended(route('dashboard'));
        }
        if (in_array('docente', $roles, true)) {
            return redirect()->intended(route('docente.portal'));
        }
        if (in_array('decano', $roles, true)) {
            return redirect()->intended(route('aprobaciones.index'));
        }

        return redirect()->intended(route('dashboard'));
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Ingrese su correo.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        $login = $validated['login'];
        $password = $validated['password'];

        // Buscar por correo en la tabla `usuarios`
        $user = Usuario::where('correo', $login)->first();
        if (!$user) {
            try { DB::table('bitacora')->insert(['id_usuario'=>null,'accion'=>'LOGIN_FAIL','tabla_afectada'=>'auth','id_afectado'=>$login,'ip_origen'=>$request->ip(),'descripcion'=>'Intento fallido (usuario no existe)','fecha'=>now()]); } catch (\Throwable $e) {}
            return back()->withErrors(['login' => 'Credenciales inválidas'])->withInput($request->only('login'));
        }

        $stored = (string) $user->contrasena;
        $isBcrypt = str_starts_with($stored, '$2y$');

        if ($isBcrypt) {
            if (Hash::check($password, $stored)) {
                Auth::login($user);
                $request->session()->regenerate();
                try { DB::table('bitacora')->insert(['id_usuario'=>$user->id_usuario,'accion'=>'LOGIN','tabla_afectada'=>'auth','id_afectado'=>(string)$user->id_usuario,'ip_origen'=>$request->ip(),'descripcion'=>'Inicio de sesión','fecha'=>now()]); } catch (\Throwable $e) {}
                return redirect()->intended(route('dashboard'));
            }
        } else {
            // Compatibilidad con datos heredados (texto plano o MD5)
            $plainMatch = hash_equals($stored, $password);
            $md5Match = (strlen($stored) === 32 && preg_match('/^[a-f0-9]{32}$/i', $stored))
                ? hash_equals(md5($password), $stored)
                : false;

            Log::debug('auth.fallback_check', [
                'correo' => $login,
                'user_id' => $user->id_usuario ?? null,
                'is_bcrypt' => $isBcrypt,
                'stored_len' => strlen($stored),
                'plain_match' => $plainMatch,
                'md5_match' => $md5Match,
            ]);

            if ($plainMatch || $md5Match) {
                // Rehash a bcrypt y continuar
                $user->contrasena = $password; // mutator aplicará bcrypt
                $user->save();
                Auth::login($user);
                $request->session()->regenerate();
                try { DB::table('bitacora')->insert(['id_usuario'=>$user->id_usuario,'accion'=>'LOGIN','tabla_afectada'=>'auth','id_afectado'=>(string)$user->id_usuario,'ip_origen'=>$request->ip(),'descripcion'=>'Inicio de sesión (rehash)','fecha'=>now()]); } catch (\Throwable $e) {}
                return redirect()->intended(route('dashboard'));
            }
        }

        try { DB::table('bitacora')->insert(['id_usuario'=>$user->id_usuario ?? null,'accion'=>'LOGIN_FAIL','tabla_afectada'=>'auth','id_afectado'=>$login,'ip_origen'=>$request->ip(),'descripcion'=>'Intento fallido (credenciales)','fecha'=>now()]); } catch (\Throwable $e) {}
        return back()->withErrors(['login' => 'Credenciales inválidas'])->withInput($request->only('login'));
    }

    public function logout(Request $request)
    {
        $uid = auth()->user()->id_usuario ?? null;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // CU1: registrar cierre de sesión en la tabla 'usuarios'
        try { DB::table('bitacora')->insert(['id_usuario'=>$uid,'accion'=>'LOGOUT','tabla_afectada'=>'usuarios','id_afectado'=>(string)($uid ?? ''),'ip_origen'=>$request->ip(),'descripcion'=>'Cierre de sesión','fecha'=>now()]); } catch (\Throwable $e) {}
        try { DB::table('bitacora')->insert(['id_usuario'=>$uid,'accion'=>'LOGOUT','tabla_afectada'=>'auth','id_afectado'=>(string)($uid ?? ''),'ip_origen'=>$request->ip(),'descripcion'=>'Cierre de sesión','fecha'=>now()]); } catch (\Throwable $e) {}
        return redirect()->route('login')->with('status', 'Sesión cerrada.');
    }
}

