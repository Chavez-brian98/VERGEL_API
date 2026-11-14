<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inspection\IndexInspectionRequest;
use App\Http\Requests\Inspection\StoreInspectionRequest;
use App\Http\Requests\Inspection\UpdateInspectionRequest;
use App\Http\Requests\Inspection\UpdateInspectionStatusRequest;
use App\Http\Requests\Inspection\UploadPhotosRequest;
use App\Http\Resources\Inspections\InspectionResource;
use App\Models\Inspection;
use App\Services\InspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @group Técnico de Campo
 *
 * Endpoints para las inspecciones de campo, relevamiento de requerimientos, servicios sugeridos, dimensiones de terreno y galería de imágenes.
 */
class InspectionController extends Controller
{
    public function __construct(
        private InspectionService $inspectionService
    ) {}

    /**
     * Listar inspecciones de campo.
     *
     * @subgroup Consultas
     *
     * @queryParam search string Búsqueda en hallazgos, notas de terreno o nombre del cliente. Example: Jardín frontal
     * @queryParam status string Filtrar por estado (draft, submitted, reviewed). Example: submitted
     * @queryParam customer_id integer Filtrar por ID de cliente. Example: 1
     * @queryParam employee_id integer Filtrar por técnico/empleado asignado. Example: 2
     * @queryParam start_date date Filtrar inspecciones desde esta fecha (YYYY-MM-DD). Example: 2026-11-01
     * @queryParam end_date date Filtrar inspecciones hasta esta fecha (YYYY-MM-DD). Example: 2026-11-30
     * @queryParam sort_by string Campo de ordenamiento (id, inspection_date, created_at, status, total_area). Default: id. Example: inspection_date
     * @queryParam sort_direction string Dirección (asc o desc). Default: desc. Example: desc
     * @queryParam per_page integer Registros por página. Example: 15
     */
    public function index(IndexInspectionRequest $request): AnonymousResourceCollection
    {
        $inspections = $this->inspectionService->list($request->validated());

        return InspectionResource::collection($inspections);
    }

    /**
     * Registrar inspección de campo.
     *
     * Crea una inspección realizada por el técnico de campo, registrando dimensiones del terreno, servicios requeridos y fotografías de referencia.
     *
     * @subgroup Escritura
     *
     * @bodyParam customer_id integer required ID del cliente visitado. Example: 1
     * @bodyParam appointment_id integer ID de la cita previa asociada si aplica. Example: 1
     * @bodyParam employee_id integer ID del técnico asignado (opcional, por defecto el usuario autenticado). Example: 2
     * @bodyParam inspection_date date required Fecha de la visita técnica (YYYY-MM-DD). Example: 2026-11-11
     * @bodyParam findings string Hallazgos, observaciones generales del lugar y necesidades del cliente. Example: Terreno con desnivel pronunciado y maleza alta. Requiere desmonte y nivelación.
     * @bodyParam terrain_width number Ancho del terreno. Example: 15.50
     * @bodyParam terrain_length number Largo del terreno. Example: 20.00
     * @bodyParam total_area number Área total (si no se especifica, se calcula multiplicando ancho x largo). Example: 310.00
     * @bodyParam area_unit string Unidad de medida del terreno (m2, v2, ha). Default: m2. Example: m2
     * @bodyParam terrain_notes string Notas sobre tipo de suelo, acceso o limitaciones físicas. Example: Suelo arcilloso, toma de agua a 10 metros.
     * @bodyParam status string Estado de la inspección (draft, submitted, reviewed). Default: draft. Example: submitted
     * @bodyParam services object[] Servicios recomendados para la cotización.
     * @bodyParam services[].service_id integer required ID del servicio. Example: 1
     * @bodyParam services[].suggested_quantity integer Cantidad sugerida. Example: 2
     * @bodyParam services[].notes string Notas técnicas del servicio. Example: Requiere fumigación previa.
     * @bodyParam photos file[] Archivos de imagen (JPEG, PNG, WEBP, máx 10MB) de la inspección.
     */
    public function store(StoreInspectionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $photos = $this->extractPhotos($request);

        $inspection = $this->inspectionService->create($data, $photos);

        return (new InspectionResource($inspection))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver detalle de inspección de campo.
     *
     * @subgroup Consultas
     */
    public function show(int $id): InspectionResource
    {
        Gate::authorize('view', Inspection::class);

        $inspection = $this->inspectionService->findById($id);

        return new InspectionResource($inspection);
    }

    /**
     * Actualizar inspección de campo.
     *
     * @subgroup Escritura
     */
    public function update(UpdateInspectionRequest $request, int $id): InspectionResource
    {
        $data = $request->validated();
        $photos = $this->extractPhotos($request);

        $inspection = $this->inspectionService->update($id, $data, $photos);

        return new InspectionResource($inspection);
    }

    /**
     * Cambiar estado de la inspección.
     *
     * @subgroup Escritura
     *
     * @bodyParam status string required Nuevo estado (draft, submitted, reviewed). Example: reviewed
     */
    public function updateStatus(UpdateInspectionStatusRequest $request, int $id): InspectionResource
    {
        $inspection = $this->inspectionService->updateStatus($id, $request->validated('status'));

        return new InspectionResource($inspection);
    }

    /**
     * Subir fotos adicionales a la inspección.
     *
     * Permite subir más imágenes de referencia a una inspección ya creada.
     *
     * @subgroup Escritura
     */
    public function uploadPhotos(UploadPhotosRequest $request, int $id): InspectionResource
    {
        $photos = $this->extractPhotos($request);
        $inspection = $this->inspectionService->addPhotos($id, $photos);

        return new InspectionResource($inspection);
    }

    /**
     * Eliminar foto de inspección.
     *
     * @subgroup Escritura
     */
    public function deletePhoto(int $inspectionId, int $photoId): JsonResponse
    {
        Gate::authorize('update', Inspection::class);

        $this->inspectionService->removePhoto($inspectionId, $photoId);

        return response()->json([
            'message' => 'Fotografía eliminada correctamente.',
        ]);
    }

    /**
     * Eliminar inspección técnica.
     *
     * @subgroup Escritura
     */
    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('delete', Inspection::class);

        $this->inspectionService->delete($id);

        return response()->json([
            'message' => 'Inspección eliminada correctamente.',
        ]);
    }

    /**
     * Extrae las fotos subidas ya sea en formato directo 'photos' o estructurado 'photos_data'.
     */
    private function extractPhotos($request): array
    {
        $photos = [];

        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
        } elseif ($request->has('photos_data')) {
            $photosData = $request->allFiles()['photos_data'] ?? [];
            $metaData = $request->input('photos_data', []);

            foreach ($photosData as $index => $item) {
                if (isset($item['file'])) {
                    $photos[] = [
                        'file' => $item['file'],
                        'caption' => $metaData[$index]['caption'] ?? null,
                    ];
                }
            }
        }

        return is_array($photos) ? $photos : [$photos];
    }
}
