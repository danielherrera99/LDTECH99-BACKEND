<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // ─── Redirección a Google ────────────────────────────────────────────────────
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    // ─── Callback de Google ──────────────────────────────────────────────────────
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect($frontendUrl . '?oauth_error=' . urlencode('Error al autenticar con Google. Inténtalo de nuevo.'));
        }

        // Buscar usuario existente por provider_id o email
        $user = User::where('provider', 'google')
                    ->where('provider_id', $socialUser->getId())
                    ->first();

        if (!$user) {
            $user = User::where('email', strtolower($socialUser->getEmail()))->first();
        }

        if (!$user) {
            // ── Usuario nuevo: crear con créditos = 0 ──────────────────────────
            $emailBase = explode('@', $socialUser->getEmail())[0];
            $username  = strtolower(preg_replace('/[^a-z0-9]/i', '', $emailBase));

            // Garantizar username único
            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            $user = User::create([
                'name'        => $socialUser->getName() ?? $socialUser->getEmail(),
                'username'    => $username,
                'email'       => strtolower($socialUser->getEmail()),
                'password'    => Hash::make(Str::random(32)), // contraseña aleatoria (no se usa)
                'role'        => 'cliente',
                'credits'     => '0',
                'avatar'      => $socialUser->getAvatar() ?? '',
                'provider'    => 'google',
                'provider_id' => $socialUser->getId(),
            ]);
        } else {
            // ── Usuario existente: actualizar avatar y provider si faltaba ─────
            $user->update([
                'provider'    => 'google',
                'provider_id' => $socialUser->getId(),
                'avatar'      => $socialUser->getAvatar() ?? $user->avatar,
            ]);
        }

        // ── Generar token de sesión (mismo formato que el login normal) ───────
        $token = 'ey.' . $user->username . '.jwt.token.simulation';

        $userData = [
            'id'       => $user->id,
            'username' => $user->name,
            'email'    => $user->email,
            'role'     => $user->role,
            'avatar'   => $user->avatar,
            'credits'  => $user->credits,
            'token'    => $token,
        ];

        // ── Redirigir al frontend con la sesión codificada ────────────────────
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $encoded     = base64_encode(json_encode($userData));

        return redirect($frontendUrl . '?oauth_token=' . urlencode($token) . '&oauth_user=' . urlencode($encoded));
    }

    // ─── Redirección a Facebook ──────────────────────────────────────────────────
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')
            ->stateless()
            ->redirect();
    }

    // ─── Callback de Facebook ────────────────────────────────────────────────────
    public function handleFacebookCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->stateless()->user();
        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect($frontendUrl . '?oauth_error=' . urlencode('Error al autenticar con Facebook. Inténtalo de nuevo.'));
        }

        // Buscar usuario existente por provider_id o email
        $user = User::where('provider', 'facebook')
                    ->where('provider_id', $socialUser->getId())
                    ->first();

        // Si no se encuentra por provider_id, buscar por email (Facebook a veces no retorna email si el usuario no lo autoriza o si se registró con número de celular)
        if (!$user && $socialUser->getEmail()) {
            $user = User::where('email', strtolower($socialUser->getEmail()))->first();
        }

        if (!$user) {
            // ── Usuario nuevo: crear con créditos = 0 ──────────────────────────
            $email = $socialUser->getEmail() ?? $socialUser->getId() . '@facebook.com'; // Fallback si no hay email
            $emailBase = explode('@', $email)[0];
            $username  = strtolower(preg_replace('/[^a-z0-9]/i', '', $emailBase));

            // Garantizar username único
            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            $user = User::create([
                'name'        => $socialUser->getName() ?? 'Usuario Facebook',
                'username'    => $username,
                'email'       => strtolower($email),
                'password'    => Hash::make(Str::random(32)), // contraseña aleatoria (no se usa)
                'role'        => 'cliente',
                'credits'     => '0',
                'avatar'      => $socialUser->getAvatar() ?? '',
                'provider'    => 'facebook',
                'provider_id' => $socialUser->getId(),
            ]);
        } else {
            // ── Usuario existente: actualizar avatar y provider si faltaba ─────
            $user->update([
                'provider'    => 'facebook',
                'provider_id' => $socialUser->getId(),
                'avatar'      => $socialUser->getAvatar() ?? $user->avatar,
            ]);
        }

        // ── Generar token de sesión (mismo formato que el login normal) ───────
        $token = 'ey.' . $user->username . '.jwt.token.simulation';

        $userData = [
            'id'       => $user->id,
            'username' => $user->name,
            'email'    => $user->email,
            'role'     => $user->role,
            'avatar'   => $user->avatar,
            'credits'  => $user->credits,
            'token'    => $token,
        ];

        // ── Redirigir al frontend con la sesión codificada ────────────────────
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $encoded     = base64_encode(json_encode($userData));

        return redirect($frontendUrl . '?oauth_token=' . urlencode($token) . '&oauth_user=' . urlencode($encoded));
    }
}
