<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\QueryResultCache;

class ConsultaController extends Controller
{
    private string $premiumToken;
    private string $freeToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->premiumToken = env('CODART_API_TOKEN', 'mkP2mNY8qlrcUC5Y0W9ycNWbfUDPelP3caquQFmDNyUt7P5QKULQfyaybHtr');
        $this->freeToken    = env('CODART_FREE_TOKEN', '5oQzLwbZ9TccLCzFhFbHXTgoGHmOsWYxyCfRyZ4FliZrCTriYl4nALdBThi3');
        $this->baseUrl      = env('CODART_API_BASE', 'https://api-codart.cgrt.org/api/v1/consultas');
    }

    // ─── Helper de Caché Centralizado de Resultados ─────────────────────────────
    private function checkCacheOrCall(string $module, string $query, bool $isFree, string $endpointUrl, array $queryParams = null)
    {
        // 1. Buscar en el caché centralizado de la base de datos (Supabase)
        $cached = QueryResultCache::where('module', $module)
            ->where('query', $query)
            ->first();

        if ($cached) {
            // Retornar de inmediato (Gasto 0 créditos, velocidad de 20ms!)
            return $this->corsResponse($cached->result_data);
        }

        // 2. Si no existe, realizar la petición externa
        try {
            $response = Http::withHeaders($this->headers($isFree));
            
            if ($queryParams) {
                $res = $response->get($endpointUrl, $queryParams);
            } else {
                $res = $response->get($endpointUrl);
            }

            $data = $res->json();

            // 3. Guardar en caché si la consulta retornó éxito
            if ($res->ok() && isset($data['success']) && $data['success'] === true) {
                QueryResultCache::updateOrCreate(
                    ['module' => $module, 'query' => $query],
                    ['result_data' => $data]
                );
            }

            return $this->corsResponse($data, $res->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── Cabeceras de autorización estándar ────────────────────────────────────
    private function headers(bool $isFree = false): array
    {
        $activeToken = $isFree ? $this->freeToken : $this->premiumToken;
        return [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $activeToken,
        ];
    }

    // ─── Respuesta uniforme con CORS habilitado ─────────────────────────────────
    private function corsResponse(array $data, int $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin',  env('FRONTEND_URL', '*'))
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
    }

    // ─── Página principal (vista Blade) ────────────────────────────────────────
    public function index()
    {
        return view('consultas.index');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  1. RUC SUNAT  ·  1 Petición
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarRuc(Request $request)
    {
        $request->validate(['ruc' => 'required|digits:11']);
        $ruc = $request->input('ruc');

        return $this->checkCacheOrCall('ruc', $ruc, true, "{$this->baseUrl}/sunat/ruc/{$ruc}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  2. DNI BÁSICO RENIEC  ·  1 Petición
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDni(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('dni_basic', $dni, true, "{$this->baseUrl}/reniec/dni/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  3. DNI PREMIUM (Foto + Firma)  ·  2 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDniPremium(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('dni_premium', $dni, false, "{$this->baseUrl}/fd/dni/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  4. DNIT EXTENDIDO (4 Imágenes Biométricas)  ·  5 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDnitExtended(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('dnit', $dni, false, "{$this->baseUrl}/fd/dnit/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  4.1. CONSULTA DNIV (2 Imágenes Biométricas)  ·  8 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDniv(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('dniv', $dni, false, "{$this->baseUrl}/fd/dniv/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  5. BÚSQUEDA POR NOMBRES (NM)  ·  4 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarNm(Request $request)
    {
        $request->validate([
            'n1'  => 'required|string|min:2',
            'ap1' => 'required|string|min:2',
            'ap2' => 'required|string|min:2',
        ]);

        $n1  = strtolower(trim($request->input('n1')));
        $ap1 = strtolower(trim($request->input('ap1')));
        $ap2 = strtolower(trim($request->input('ap2')));
        $queryKey = "{$n1}:{$ap1}:{$ap2}";

        return $this->checkCacheOrCall('nm', $queryKey, false, "{$this->baseUrl}/fd/nm", [
            'n1'  => $request->input('n1'),
            'ap1' => $request->input('ap1'),
            'ap2' => $request->input('ap2'),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  6. ÁRBOL GENEALÓGICO (AG)  ·  8 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarAg(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('ag', $dni, false, "{$this->baseUrl}/fd/ag/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  7. LÍNEAS TELEFÓNICAS (TELP)  ·  15 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarTelp(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('telp', $dni, false, "{$this->baseUrl}/fd/telp/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  8. BÚSQUEDA INVERSA CEL (TELP CEL)  ·  15 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarTelpCel(Request $request)
    {
        $request->validate(['numero' => 'required|digits:9']);
        $numero = $request->input('numero');

        return $this->checkCacheOrCall('telp_cel', $numero, false, "{$this->baseUrl}/fd/telp/cel/{$numero}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  9. PLACA VEHICULAR (PLA)  ·  2 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarPla(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        return $this->checkCacheOrCall('pla', $placa, false, "{$this->baseUrl}/fd/pla/{$placa}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  10. PLACAS Y PROPIETARIOS COMPLETO (PLAT)  ·  5 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarPlat(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        return $this->checkCacheOrCall('plat', $placa, false, "{$this->baseUrl}/fd/plat/{$placa}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  11. HISTORIAL DE SOAT POR PLACA (HSOAT)  ·  8 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarHsoat(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        return $this->checkCacheOrCall('hsoat', $placa, false, "{$this->baseUrl}/fd/hsoat/{$placa}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  12. RECONOCIMIENTO FACIAL (FACIAL)  ·  45 Créditos  (No se cachea por ser biométrico dinámico)
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarFacial(Request $request)
    {
        $request->validate(['image_facial' => 'required|file|image']);

        try {
            $file = $request->file('image_facial');
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->premiumToken,
            ])->attach(
                'image_facial',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post("{$this->baseUrl}/fd/facial");

            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  13. RECONOCIMIENTO FACIAL TOP (FACIAL TOP)  ·  50 Créditos  (No se cachea por ser biométrico dinámico)
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarFacialTop(Request $request)
    {
        $request->validate(['image_facial' => 'required|file|image']);

        try {
            $file = $request->file('image_facial');
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->premiumToken,
            ])->attach(
                'image_facial',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post("{$this->baseUrl}/fd/facial/top");

            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  14. DENUNCIAS POLICIALES (RÉCORD TEXTO)  ·  15 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDen(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('den', $dni, false, "{$this->baseUrl}/fd/den/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  15. DESCARGA DE ACTAS DE DENUNCIA (PDF)  ·  20 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDenuncias(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('denuncias', $dni, false, "{$this->baseUrl}/fd/denuncias/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  16. REQUISITORIAS JUDICIALES (RQH)  ·  10 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarRqh(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        return $this->checkCacheOrCall('rqh', $dni, false, "{$this->baseUrl}/fd/rqh/{$dni}");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  17. DENUNCIAS VEHICULARES POR PLACA (DENPLA)  ·  30 Créditos
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDenpla(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        return $this->checkCacheOrCall('denpla', $placa, false, "{$this->baseUrl}/fd/denpla/{$placa}");
    }
}
