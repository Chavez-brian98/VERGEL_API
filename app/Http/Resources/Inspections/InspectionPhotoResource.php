<?php

namespace App\Http\Resources\Inspections;

use App\Helpers\FileUploadHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionPhotoResource extends JsonResource
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
            'imagePath' => $this->image_path,
            'imageUrl' => FileUploadHelper::url($this->image_path),
            'caption' => $this->caption,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
