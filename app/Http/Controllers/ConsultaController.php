<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
    //  1. RUC SUNAT  ·  1 Petición  ·  GET /sunat/ruc/{ruc}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarRuc(Request $request)
    {
        $request->validate(['ruc' => 'required|digits:11']);
        $ruc = $request->input('ruc');

        try {
            $response = Http::withHeaders($this->headers(true))
                ->get("{$this->baseUrl}/sunat/ruc/{$ruc}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  2. DNI BÁSICO RENIEC  ·  1 Petición  ·  GET /reniec/dni/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDni(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers(true))
                ->get("{$this->baseUrl}/reniec/dni/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  3. DNI PREMIUM (Foto + Firma)  ·  2 Créditos  ·  GET /fd/dni/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDniPremium(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/dni/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  4. DNIT EXTENDIDO (4 Imágenes Biométricas)  ·  5 Créditos  ·  GET /fd/dnit/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDnitExtended(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/dnit/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  5. BÚSQUEDA POR NOMBRES (NM)  ·  4 Créditos  ·  GET /fd/nm?n1=&ap1=&ap2=
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarNm(Request $request)
    {
        $request->validate([
            'n1'  => 'required|string|min:2',
            'ap1' => 'required|string|min:2',
            'ap2' => 'required|string|min:2',
        ]);

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/nm", [
                    'n1'  => $request->input('n1'),
                    'ap1' => $request->input('ap1'),
                    'ap2' => $request->input('ap2'),
                ]);
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  6. ÁRBOL GENEALÓGICO (AG)  ·  8 Créditos  ·  GET /fd/ag/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarAg(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/ag/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  7. LÍNEAS TELEFÓNICAS (TELP)  ·  15 Créditos  ·  GET /fd/telp/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarTelp(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/telp/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  8. BÚSQUEDA INVERSA CEL (TELP CEL)  ·  15 Créditos  ·  GET /fd/telp/cel/{numero}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarTelpCel(Request $request)
    {
        $request->validate(['numero' => 'required|digits:9']);
        $numero = $request->input('numero');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/telp/cel/{$numero}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  9. PLACA VEHICULAR (PLA)  ·  2 Créditos  ·  GET /fd/pla/{placa}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarPla(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/pla/{$placa}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  10. PLACAS Y PROPIETARIOS COMPLETO (PLAT)  ·  5 Créditos  ·  GET /fd/plat/{placa}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarPlat(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/plat/{$placa}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  11. HISTORIAL DE SOAT POR PLACA (HSOAT)  ·  8 Créditos  ·  GET /fd/hsoat/{placa}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarHsoat(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/hsoat/{$placa}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  12. RECONOCIMIENTO FACIAL (FACIAL)  ·  45 Créditos  ·  POST /fd/facial
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
    //  13. RECONOCIMIENTO FACIAL TOP (FACIAL TOP)  ·  50 Créditos  ·  POST /fd/facial/top
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
    //  14. DENUNCIAS POLICIALES (RÉCORD TEXTO)  ·  15 Créditos  ·  GET /fd/den/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDen(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/den/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  15. DESCARGA DE ACTAS DE DENUNCIA (PDF)  ·  20 Créditos  ·  GET /fd/denuncias/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDenuncias(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/denuncias/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  16. REQUISITORIAS JUDICIALES (RQH)  ·  10 Créditos  ·  GET /fd/rqh/{dni}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarRqh(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        $dni = $request->input('dni');

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/rqh/{$dni}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  17. DENUNCIAS VEHICULARES POR PLACA (DENPLA)  ·  30 Créditos  ·  GET /fd/denpla/{placa}
    // ═══════════════════════════════════════════════════════════════════════════
    public function consultarDenpla(Request $request)
    {
        $request->validate(['placa' => 'required|string|min:6|max:7|regex:/^[A-Z0-9]+$/']);
        $placa = strtoupper($request->input('placa'));

        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fd/denpla/{$placa}");
            return $this->corsResponse($response->json(), $response->status());
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
