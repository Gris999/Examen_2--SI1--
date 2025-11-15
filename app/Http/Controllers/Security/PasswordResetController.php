<?php

namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLink(Request $request)
    {
        // CU1: Validar correo institucional y generar token
        $correo = $request->input('correo', $request->input('email'));
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['correo' => $correo],
            [
                'correo' => ['required', 'email', 'regex:/@ficct\\.edu\\.bo$/i'],
            ],
            [
                'correo.required' => 'Ingrese su correo institucional.',
                'correo.email' => 'Correo inválido.',
                'correo.regex' => 'Use su correo institucional (@ficct.edu.bo).',
            ]
        );
        if ($validator->fails()) {
            return back()->withErrors(['email' => $validator->errors()->first('correo')])->withInput(['email' => $correo]);
        }

        $user = Usuario::where('correo', $correo)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No existe un usuario con ese correo.'])->withInput(['email' => $correo]);
        }

        $plainToken = \Illuminate\Support\Str::random(64);
        $hashed = \Illuminate\Support\Facades\Hash::make($plainToken);

        \Illuminate\Support\Facades\DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $correo)
            ->delete();

        \Illuminate\Support\Facades\DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->insert([
                'email' => $correo,
                'token' => $hashed,
                'created_at' => \Carbon\Carbon::now(),
            ]);

        $devLink = route('password.reset.form', ['token' => $plainToken, 'email' => $correo]);

        return redirect()->route('password.sent')
            ->with('status', 'Se ha enviado un enlace a tu correo')
            ->with('dev_link', $devLink)
            ->with('email', $correo);
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = Usuario::where('correo', $data['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No existe un usuario con ese correo.'])->withInput();
        }

        $plainToken = Str::random(64);
        $hashed = Hash::make($plainToken);

        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $user->correo)
            ->delete();

        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->insert([
                'email' => $user->correo,
                'token' => $hashed,
                'created_at' => Carbon::now(),
            ]);

        $devLink = route('password.reset.form', ['token' => $plainToken, 'email' => $user->correo]);

        return redirect()->route('password.sent')
            ->with('status', 'Te enviamos un enlace para restablecer tu contraseña.')
            ->with('dev_link', $devLink)
            ->with('email', $user->correo);
    }

    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function reset(Request $request)
    {
        // CU1: Restablecer contraseña con validaciones y registrar en bitácora
        $correo = $request->input('correo', $request->input('email'));
        $contrasena = $request->input('contrasena', $request->input('password'));
        $contrasenaConf = $request->input('contrasena_confirmation', $request->input('password_confirmation'));

        $validator = \Illuminate\Support\Facades\Validator::make(
            [
                'email' => $correo,
                'token' => $request->input('token'),
                'password' => $contrasena,
                'password_confirmation' => $contrasenaConf,
            ],
            [
                'email' => ['required', 'email'],
                'token' => ['required', 'string'],
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]
        );
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput(['email' => $correo]);
        }

        $record = \Illuminate\Support\Facades\DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $correo)
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Token inválido o expirado.']);
        }

        if (\Carbon\Carbon::parse($record->created_at)->lt(now()->subMinutes(config('auth.passwords.users.expire', 60)))) {
            return back()->withErrors(['email' => 'El token ha expirado.']);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->input('token'), $record->token)) {
            return back()->withErrors(['token' => 'Token inválido.']);
        }

        $user = Usuario::where('correo', $correo)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Usuario no encontrado.']);
        }

        $user->contrasena = $contrasena;
        $user->save();

        \Illuminate\Support\Facades\DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $correo)
            ->delete();

        // Registrar en bitácora
        try {
            \Illuminate\Support\Facades\DB::table('bitacora')->insert([
                'id_usuario' => $user->id_usuario,
                'accion' => 'RECUPERAR_CONTRASEÑA',
                'tabla_afectada' => 'usuarios',
                'id_afectado' => (string) $user->id_usuario,
                'ip_origen' => $request->ip(),
                'descripcion' => 'Contraseña restablecida',
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {}

        auth()->login($user);

        return redirect()->route('dashboard')->with('status', 'Contraseña actualizada.');
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $record = DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $data['email'])
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Token inválido o expirado.']);
        }

        // Expiración de 60 minutos
        if (Carbon::parse($record->created_at)->lt(now()->subMinutes(config('auth.passwords.users.expire', 60)))) {
            return back()->withErrors(['email' => 'El token ha expirado.']);
        }

        if (!Hash::check($data['token'], $record->token)) {
            return back()->withErrors(['token' => 'Token inválido.']);
        }

        $user = Usuario::where('correo', $data['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Usuario no encontrado.']);
        }

        $user->contrasena = $data['password'];
        $user->save();

        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $data['email'])
            ->delete();

        // Iniciar sesión tras reset
        auth()->login($user);

        return redirect()->route('dashboard')->with('status', 'Contraseña actualizada.');
    }
}
