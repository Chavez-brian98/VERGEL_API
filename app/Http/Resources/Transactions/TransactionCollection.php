<?php

namespace App\Http\Resources\Transactions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionCollection extends ResourceCollection
{
    /**
     * El recurso de elemento individual para esta colección.
     *
     * @var string
     */
    public $collects = TransactionResource::class;

    /**
     * Arreglo con las métricas del resumen por cliente / conjunto filtrado.
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
     * @return array<int, mixed>
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
            'meta' => [
                'summary' => $this->summary,
            ],
            'message' => $isEmpty
                ? 'No se encontraron registros de trabajo en los filtros aplicados'
                : 'Historial de transacciones obtenido exitosamente.',
        ];
    }
}
