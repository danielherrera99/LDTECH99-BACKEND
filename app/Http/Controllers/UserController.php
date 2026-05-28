<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // ─── Respuesta uniforme con CORS habilitado ─────────────────────────────────
    private function corsResponse(array $data, int $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin',  env('FRONTEND_URL', '*'))
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
    }

    // 1. LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $user = User::whereRaw('LOWER(username) = ?', [strtolower(trim($username))])->first();

        if ($user && Hash::check($password, $user->password)) {
            $userData = [
                'id' => $user->id,
                'username' => $user->name, // name matches dynamic dashboard role
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'credits' => $user->credits,
                'token' => 'ey.' . $user->username . '.jwt.token.simulation'
            ];
            return $this->corsResponse(['success' => true, 'user' => $userData]);
        }

        return $this->corsResponse(['success' => false, 'message' => 'Credenciales incorrectas. Verifique e intente de nuevo.'], 401);
    }

    // 2. LISTAR USUARIOS
    public function index()
    {
        $users = User::all()->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'credits' => $user->credits,
            ];
        });

        return $this->corsResponse(['success' => true, 'users' => $users]);
    }

    // 3. CREAR USUARIO
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:4',
            'role' => 'required|string',
            'credits' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'username' => strtolower(trim($request->input('username'))),
            'email' => strtolower(trim($request->input('email'))),
            'password' => Hash::make($request->input('password')),
            'role' => $request->input('role'),
            'avatar' => $request->input('avatar') ?: 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=150&h=150&q=80',
            'credits' => $request->input('credits'),
        ]);

        return $this->corsResponse([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'credits' => $user->credits,
            ]
        ]);
    }

    // 4. ACTUALIZAR USUARIO
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->corsResponse(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|string',
            'credits' => 'required|string',
        ]);

        $user->name = $request->input('name');
        $user->username = strtolower(trim($request->input('username')));
        $user->email = strtolower(trim($request->input('email')));
        $user->role = $request->input('role');
        $user->credits = $request->input('credits');

        if ($request->input('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        if ($request->input('avatar')) {
            $user->avatar = $request->input('avatar');
        }

        $user->save();

        return $this->corsResponse([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'credits' => $user->credits,
            ]
        ]);
    }

    // 5. ELIMINAR USUARIO
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->corsResponse(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        // Impedir eliminar el administrador principal
        if ($user->username === 'ldtech') {
            return $this->corsResponse(['success' => false, 'message' => 'No está permitido eliminar la cuenta del Administrador Principal.'], 403);
        }

        $user->delete();

        return $this->corsResponse(['success' => true, 'message' => 'Usuario eliminado con éxito.']);
    }
}
