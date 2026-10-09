<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plant\StorePlantRequest;
use App\Http\Resources\Plants\PlantResource;
use App\Services\PlantService;
use Illuminate\Support\Facades\Log;

class PlantController extends Controller
{
    public function __construct(
        private PlantService $plantService
    ) {}

    /**
     * Registrar una nueva planta.
     *
     * Permite agregar una nueva especie o planta ornamental al catálogo, con su respectiva información de precio, categoría opcional y archivo de imagen.
     *
     * @group Plantas
     *
     * @subgroup Escritura
     *
     * @bodyParam name string required Nombre único de la planta o especie. Example: Monstera Deliciosa
     * @bodyParam price number required Precio unitario de la planta. Example: 24.50
     * @bodyParam category_type string Categoría o ambiente de la planta (ej. Interior, Exterior, Sombra). Example: Interior
     * @bodyParam image file Archivo de imagen de la planta (formatos permitidos: jpeg, png, jpg, webp; máx: 5MB).
     * @bodyParam active boolean Estado de disponibilidad en catálogo. Por defecto true. Example: true
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "name": "Monstera Deliciosa",
     *     "price": 24.5,
     *     "categoryType": "Interior",
     *     "imageUrl": "http://localhost/storage/plants/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.webp",
     *     "imagePath": "plants/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.webp",
     *     "active": true,
     *     "createdAt": "2026-10-09T16:00:00+00:00",
     *     "updatedAt": "2026-10-09T16:00:00+00:00"
     *   }
     * }
     * @response status=422 {
     *   "message": "El nombre de la planta es obligatorio.",
     *   "errors": {
     *     "name": [
     *       "El nombre de la planta es obligatorio."
     *     ]
     *   }
     * }
     * @response status=500 {
     *   "message": "Error interno al crear la planta",
     *   "codigo": 500
     * }
     */
    public function store(StorePlantRequest $request)
    {
        try {
            $image = $request->file('image') ?? $request->file('image_url');
            $plant = $this->plantService->create($request->validated(), $image);

            return (new PlantResource($plant))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Error creating plant: '.$e->getMessage());

            return response()->json([
                'message' => 'Error interno al crear la planta',
                'codigo' => 500,
            ], 500);
        }
    }
}
