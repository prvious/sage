<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorktreeResource extends JsonResource
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
            'branch_name' => $this->branch_name,
            'path' => $this->path,
            'preview_url' => $this->preview_url,
            'status' => $this->status,
            'project' => new ProjectResource($this->whenLoaded('project')),
        ];
    }
}
