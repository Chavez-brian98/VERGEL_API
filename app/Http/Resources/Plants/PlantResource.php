<?php

namespace App\Http\Resources\Plants;

use App\Helpers\FileUploadHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'categoryType' => $this->category_type,
            'imageUrl' => FileUploadHelper::url($this->image_url),
            'imagePath' => $this->image_url,
            'active' => (bool) $this->active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
