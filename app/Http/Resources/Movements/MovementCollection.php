<?php

namespace App\Http\Resources\Movements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MovementCollection extends ResourceCollection
{
    /**
     * El recurso individual recolectado.
     *
     * @var string
     */
    public $collects = MovementResource::class;

    /**
     * Métricas del resumen financiero y cálculo de utilidad en tiempo real.
     *
     * @var array<string, mixed>
     */
    protected array $summary = [];

    /**
     * Constructor con inyección de resumen opcional.
     *
     * @param  mixed  $resource
     * @param  array<string, mixed>  $summary
     */
    public function __construct($resource, array $summary = [])
    {
        parent::__construct($resource);
        $this->summary = $summary;
    }

    /**
     * Transforma la colección a un arreglo.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Metadatos adicionales inyectados en la raíz de la respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $isEmpty = $this->collection->isEmpty();

        return [
            'summary' => $this->summary,
            'meta' => [
                'summary' => $this->summary,
            ],
            'message' => $isEmpty
                ? 'No se encontraron movimientos registrados con los filtros aplicados.'
                : 'Movimientos contables obtenidos exitosamente.',
        ];
    }
}
