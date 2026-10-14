<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plant\IndexPlantRequest;
use App\Http\Requests\Plant\StorePlantRequest;
use App\Http\Requests\Plant\UpdatePlantRequest;
use App\Http\Resources\Plants\PlantResource;
use App\Services\PlantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PlantController extends Controller
{
    public function __construct(
        private PlantService $plantService
    ) {}

    /**
     * Listar y filtrar plantas.
     *
     * Obtiene una lista paginada de plantas del catálogo con soporte para filtros dinámicos (búsqueda, categoría, rango de precio, estado) y ordenamiento.
     *
     * @group Plantas
     *
     * @subgroup Consultas
     *
     * @queryParam search string Búsqueda por coincidencia en nombre o tipo de categoría. Example: Monstera
     * @queryParam category_type string Filtrar por categoría exacta. Example: Interior
     * @queryParam active boolean Filtrar por estado activo o inactivo (true/false). Example: true
     * @queryParam min_price number Filtrar por precio mínimo. Example: 10.00
     * @queryParam max_price number Filtrar por precio máximo. Example: 50.00
     * @queryParam sort_by string Campo por el cual ordenar (id, name, price, category_type, created_at). Por defecto 'id'. Example: name
     * @queryParam sort_direction string Dirección de ordenamiento (asc o desc). Por defecto 'desc'. Example: asc
     * @queryParam per_page integer Cantidad de resultados por página (1-100). Por defecto 15. Example: 15
     * @queryParam limit integer Alias alternativo para per_page. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Monstera Deliciosa",
     *       "price": 24.5,
     *       "categoryType": "Interior",
     *       "imageUrl": "http://localhost/storage/plants/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.webp",
     *       "imagePath": "plants/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.webp",
     *       "active": true,
     *       "createdAt": "2026-10-09T16:00:00+00:00",
     *       "updatedAt": "2026-10-09T16:00:00+00:00"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/plants?page=1",
     *     "last": "http://localhost/api/v1/plants?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/plants",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(IndexPlantRequest $request)
    {
        $perPage = (int) ($request->input('per_page') ?? $request->input('limit', 15));
        $plants = $this->plantService->search($request->validated(), $perPage);

        return PlantResource::collection($plants);
    }

    /**
     * Obtener el detalle de una planta.
     *
     * Devuelve la información completa de una planta individual mediante su identificador único (ID).
     *
     * @group Plantas
     *
     * @subgroup Consultas
     *
     * @urlParam plant integer required ID de la planta. Example: 1
     *
     * @response status=200 {
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
     * @response status=404 {
     *   "message": "Planta no encontrada",
     *   "codigo": 404
     * }
     * @response status=500 {
     *   "message": "Error interno al obtener la planta",
     *   "codigo": 500
     * }
     */
    public function show($id)
    {
        try {
            $plant = $this->plantService->getById((int) $id);

            return new PlantResource($plant);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error showing plant: '.$e->getMessage());

            return response()->json([
                'message' => 'Error interno al obtener la planta',
                'codigo' => 500,
            ], 500);
        }
    }

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

    /**
     * Actualizar una planta.
     *
     * Modifica los datos de una planta existente en el catálogo, permitiendo reemplazar su imagen o eliminarla.
     *
     * @group Plantas
     *
     * @subgroup Escritura
     *
     * @urlParam plant integer required ID de la planta a actualizar. Example: 1
     *
     * @bodyParam name string optional Nombre único de la planta o especie. Example: Monstera Variegata
     * @bodyParam price number optional Precio unitario de la planta. Example: 35.00
     * @bodyParam category_type string optional Categoría o ambiente de la planta (ej. Interior, Exterior, Sombra). Example: Interior
     * @bodyParam image file optional Nueva imagen para reemplazar la existente (formatos permitidos: jpeg, png, jpg, webp; máx: 5MB).
     * @bodyParam active boolean optional Estado de disponibilidad en catálogo. Example: true
     * @bodyParam remove_image boolean optional Si se envía en true, elimina la imagen actual de la planta. Example: false
     *
     * @response status=200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Monstera Variegata",
     *     "price": 35.0,
     *     "categoryType": "Interior",
     *     "imageUrl": "http://localhost/storage/plants/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.webp",
     *     "imagePath": "plants/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.webp",
     *     "active": true,
     *     "createdAt": "2026-10-09T16:00:00+00:00",
     *     "updatedAt": "2026-10-13T23:20:00+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Planta no encontrada",
     *   "codigo": 404
     * }
     * @response status=422 {
     *   "message": "Ya existe otra planta registrada con este nombre.",
     *   "errors": {
     *     "name": [
     *       "Ya existe otra planta registrada con este nombre."
     *     ]
     *   }
     * }
     * @response status=500 {
     *   "message": "Error interno al actualizar la planta",
     *   "codigo": 500
     * }
     */
    public function update(UpdatePlantRequest $request, $id)
    {
        try {
            $image = $request->file('image') ?? $request->file('image_url');
            $plant = $this->plantService->update((int) $id, $request->validated(), $image);

            return new PlantResource($plant);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating plant: '.$e->getMessage());

            return response()->json([
                'message' => 'Error interno al actualizar la planta',
                'codigo' => 500,
            ], 500);
        }
    }

    /**
     * Eliminar lógicamente una planta.
     *
     * Realiza un borrado lógico (soft delete) de la planta especificada. No permite la eliminación si la planta está asociada a cotizaciones existentes.
     *
     * @group Plantas
     *
     * @subgroup Escritura
     *
     * @urlParam plant integer required ID de la planta a eliminar. Example: 1
     *
     * @response status=204
     * @response status=404 {
     *   "message": "Planta no encontrada",
     *   "codigo": 404
     * }
     * @response status=409 {
     *   "message": "No se puede eliminar la planta porque está asociada a 2 cotización(es) o presupuesto(s).",
     *   "codigo": 409,
     *   "errors": {
     *     "plant": [
     *       "No se puede eliminar la planta porque está asociada a 2 cotización(es) o presupuesto(s)."
     *     ]
     *   }
     * }
     * @response status=500 {
     *   "message": "Error interno al eliminar la planta",
     *   "codigo": 500
     * }
     */
    public function destroy($id)
    {
        try {
            $this->plantService->delete((int) $id);

            return response()->json(null, 204);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'codigo' => 409,
                'errors' => $e->errors(),
            ], 409);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting plant: '.$e->getMessage());

            return response()->json([
                'message' => 'Error interno al eliminar la planta',
                'codigo' => 500,
            ], 500);
        }
    }
}
