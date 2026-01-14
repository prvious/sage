<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'agent_type' => $this->agent_type,
            'model' => $this->model,
            'agent_output' => $this->agent_output,
            'worktree' => new WorktreeResource($this->whenLoaded('worktree')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'commits' => CommitResource::collection($this->whenLoaded('commits')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'is_running' => $this->isRunning(),
        ];
    }
}
