<?php

namespace App\Actions\Settings;

use App\Models\Project;

final readonly class UpdateProjectSettings
{
    /**
     * Update project settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data): bool
    {
        return $project->update($data);
    }
}
