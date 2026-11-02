<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryConfig\StoreCountryConfigRequest;
use App\Http\Requests\CountryConfig\UpdateCountryConfigRequest;
use App\Http\Resources\CountryConfigs\CountryConfigResource;
use App\Services\CountryConfigService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class CountryConfigController extends Controller
{
    public function __construct(
        private CountryConfigService $countryConfigService
    ) {}

    /**
     * Listar configuraciones de país (tarifas).
     *
     * Obtiene el listado de configuraciones por país con moneda, IVA y comisión de tarjeta.
     *
     * @group Tarifas
     *
     * @subgroup Consultas
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "country": "El Salvador",
     *       "currency": "USD",
     *       "taxPercentage": 13.00,
     *       "cardFeePercentage": 3.50,
     *       "createdAt": "2026-10-08T22:58:33+00:00",
     *       "updatedAt": "2026-10-08T22:58:33+00:00"
     *     }
     *   ]
     * }
     */
    public function index()
    {
        $countries = $this->countryConfigService->all();

        return CountryConfigResource::collection($countries);
    }

    /**
     * Registrar configuración de país (tarifa).
     *
     * Crea la configuración de tarifas para un país. El nombre del país debe ser único.
     *
     * @group Tarifas
     *
     * @subgroup Escritura
     *
     * @bodyParam country string required Nombre del país. Example: El Salvador
     * @bodyParam currency string required Moneda (código ISO 4217). Example: USD
     * @bodyParam tax_percentage number required Porcentaje de IVA/impuesto. Example: 13.00
     * @bodyParam card_fee_percentage number Porcentaje de comisión por tarjeta. Example: 3.50
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "country": "El Salvador",
     *     "currency": "USD",
     *     "taxPercentage": 13.00,
     *     "cardFeePercentage": 3.50,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=422 {
     *   "message": "The country has already been taken.",
     *   "errors": {
     *     "country": ["The country has already been taken."]
     *   }
     * }
     */
    public function store(StoreCountryConfigRequest $request)
    {
        try {
            $countryConfig = $this->countryConfigService->create($request->validated());

            return (new CountryConfigResource($countryConfig))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Error creating country config: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al crear la configuración de país',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }

    /**
     * Obtener detalle de configuración de país (tarifa).
     *
     * Devuelve la configuración de tarifas para el país indicado.
     *
     * @group Tarifas
     *
     * @subgroup Consultas
     *
     * @urlParam country_config integer required ID de la configuración. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "country": "El Salvador",
     *     "currency": "USD",
     *     "taxPercentage": 13.00,
     *     "cardFeePercentage": 3.50,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Configuración de país no encontrada",
     *   "codigo": 404
     * }
     */
    public function show($id)
    {
        try {
            $countryConfig = $this->countryConfigService->getById($id);

            return new CountryConfigResource($countryConfig);
        } catch (ModelNotFoundException $e) {
            $data = [
                'message' => 'Configuración de país no encontrada',
                'codigo' => 404,
            ];

            return response()->json($data, 404);
        } catch (\Exception $e) {
            Log::error('Error showing country config: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al obtener la configuración de país',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }

    /**
     * Actualizar configuración de país (tarifa).
     *
     * Actualiza de forma parcial los datos de tarifas de un país. Registra la operación en auditoría.
     *
     * @group Tarifas
     *
     * @subgroup Escritura
     *
     * @urlParam country_config integer required ID de la configuración. Example: 1
     *
     * @bodyParam country string Nombre del país. Example: El Salvador
     * @bodyParam currency string Moneda (código ISO 4217). Example: USD
     * @bodyParam tax_percentage number Porcentaje de IVA/impuesto. Example: 13.00
     * @bodyParam card_fee_percentage number Porcentaje de comisión por tarjeta. Example: 3.50
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "country": "El Salvador",
     *     "currency": "USD",
     *     "taxPercentage": 13.00,
     *     "cardFeePercentage": 3.50,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Configuración de país no encontrada",
     *   "codigo": 404
     * }
     * @response status=422 {
     *   "message": "The country has already been taken.",
     *   "errors": {
     *     "country": ["The country has already been taken."]
     *   }
     * }
     */
    public function update(UpdateCountryConfigRequest $request, $id)
    {
        try {
            $countryConfig = $this->countryConfigService->update($id, $request->validated());

            return (new CountryConfigResource($countryConfig))->response();
        } catch (ModelNotFoundException $e) {
            $data = [
                'message' => 'Configuración de país no encontrada',
                'codigo' => 404,
            ];

            return response()->json($data, 404);
        } catch (\Exception $e) {
            Log::error('Error updating country config: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al actualizar la configuración de país',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }

    /**
     * Eliminar configuración de país (tarifa).
     *
     * Elimina la configuración de tarifas para un país.
     *
     * @group Tarifas
     *
     * @subgroup Escritura
     *
     * @urlParam country_config integer required ID de la configuración. Example: 1
     *
     * @response status=204
     * @response status=404 {
     *   "message": "Configuración de país no encontrada",
     *   "codigo": 404
     * }
     */
    public function destroy($id)
    {
        try {
            $this->countryConfigService->delete($id);

            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            $data = [
                'message' => 'Configuración de país no encontrada',
                'codigo' => 404,
            ];

            return response()->json($data, 404);
        } catch (\Exception $e) {
            Log::error('Error deleting country config: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al eliminar la configuración de país',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }
}
