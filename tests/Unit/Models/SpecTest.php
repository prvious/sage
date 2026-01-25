
<?php

use App\Models\Project;
use App\Models\Spec;

it('belongs to a project', function () {
    $project = Project::factory()->create();
    $spec = Spec::factory()->create(['project_id' => $project->id]);

    expect($spec->project)->toBeInstanceOf(Project::class);
    expect($spec->project->id)->toBe($project->id);
});
