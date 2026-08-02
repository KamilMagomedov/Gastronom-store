<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaticPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->resource->title,
            'last_updated' => $this->resource->updated_at,
            'sections' => $this->resource->sections->map(function ($section) {
                $sectionData = [
                    'number' => $section->number,
                    'title' => $section->title,
                    'content' => $section->content,
                ];

                if ($section->important_note) {
                    $sectionData['important_note'] = $section->important_note;
                }

                if ($section->requirements->isNotEmpty()) {
                    $sectionData['requirements'] = $section->requirements->pluck('requirement');
                }

                return $sectionData;
            })->toArray(),
        ];
    }
}
