<?php

use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/consultas', [ConsultaController::class, 'index'])->name('consultas.index');

// ─── Rutas de Consulta OSINT - LDTech99 Gateway ──────────────────────────────

// 1. RUC SUNAT  ·  1 Petición
Route::post('/consultas/ruc', [ConsultaController::class, 'consultarRuc'])->name('consultas.ruc');

// 2. DNI Básico RENIEC  ·  1 Petición
Route::post('/consultas/dni', [ConsultaController::class, 'consultarDni'])->name('consultas.dni');

// 3. DNI Premium (Foto + Firma)  ·  2 Créditos
Route::post('/consultas/dni-premium', [ConsultaController::class, 'consultarDniPremium'])->name('consultas.dniPremium');

// 4. DNIT Extendido (4 Imágenes Biométricas)  ·  5 Créditos
Route::post('/consultas/dnit-extended', [ConsultaController::class, 'consultarDnitExtended'])->name('consultas.dnitExtended');

// 5. Búsqueda por Nombres (NM)  ·  4 Créditos
Route::post('/consultas/nm', [ConsultaController::class, 'consultarNm'])->name('consultas.nm');

// 6. Árbol Genealógico (AG)  ·  8 Créditos
Route::post('/consultas/ag', [ConsultaController::class, 'consultarAg'])->name('consultas.ag');

// 7. Líneas Telefónicas (TELP)  ·  15 Créditos
Route::post('/consultas/telp', [ConsultaController::class, 'consultarTelp'])->name('consultas.telp');

// 8. Búsqueda Inversa Celular (TELP CEL)  ·  15 Créditos
Route::post('/consultas/telp-cel', [ConsultaController::class, 'consultarTelpCel'])->name('consultas.telpCel');

// 9. Placa Vehicular (PLA)  ·  2 Créditos
Route::post('/consultas/pla', [ConsultaController::class, 'consultarPla'])->name('consultas.pla');

// 10. Placas y Propietarios Completo (PLAT)  ·  5 Créditos
Route::post('/consultas/plat', [ConsultaController::class, 'consultarPlat'])->name('consultas.plat');

// 11. Historial de SOAT por Placa (HSOAT)  ·  8 Créditos
Route::post('/consultas/hsoat', [ConsultaController::class, 'consultarHsoat'])->name('consultas.hsoat');

// 12. Reconocimiento Facial (FACIAL)  ·  45 Créditos
Route::post('/consultas/facial', [ConsultaController::class, 'consultarFacial'])->name('consultas.facial');

// 13. Reconocimiento Facial Top (FACIAL TOP)  ·  50 Créditos
Route::post('/consultas/facial-top', [ConsultaController::class, 'consultarFacialTop'])->name('consultas.facialTop');

// 14. Denuncias Policiales Récord Texto (DEN)  ·  15 Créditos
Route::post('/consultas/den', [ConsultaController::class, 'consultarDen'])->name('consultas.den');

// 15. Descarga de Actas de Denuncia PDF (DEN PDF)  ·  20 Créditos
Route::post('/consultas/denuncias', [ConsultaController::class, 'consultarDenuncias'])->name('consultas.denuncias');

// 16. Requisitorias Judiciales (RQH)  ·  10 Créditos
Route::post('/consultas/rqh', [ConsultaController::class, 'consultarRqh'])->name('consultas.rqh');

// 17. Denuncias Vehiculares por Placa (DENPLA)  ·  30 Créditos
Route::post('/consultas/denpla', [ConsultaController::class, 'consultarDenpla'])->name('consultas.denpla');

// ─── Gestión de Usuarios y Autenticación Centralizada ─────────────────────────
Route::post('/auth/login', [UserController::class, 'login']);
Route::get('/auth/users', [UserController::class, 'index']);
Route::post('/auth/users', [UserController::class, 'store']);
Route::put('/auth/users/{id}', [UserController::class, 'update']);
Route::delete('/auth/users/{id}', [UserController::class, 'destroy']);
Route::get('/auth/history', [UserController::class, 'getHistory']);
Route::post('/auth/history', [UserController::class, 'addHistory']);
Route::delete('/auth/history', [UserController::class, 'clearHistory']);

// ─── Autenticación Social (OAuth) ──────────────────────────────────────────────
Route::get('/auth/google',          [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/auth/facebook',          [SocialAuthController::class, 'redirectToFacebook']);
Route::get('/auth/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);

// ─── Disparador Web Seguro para Migraciones en Hosting Gratis (Render) ───────
Route::get('/db-migrate-secure-trigger', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true
        ]);
        return response()->json([
            'success' => true,
            'message' => '¡Base de datos migrada y sembrada con éxito en Supabase!',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al migrar la base de datos: ' . $e->getMessage()
        ], 500);
    }
});