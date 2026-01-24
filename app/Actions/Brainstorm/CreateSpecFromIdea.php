<?php

declare(strict_types=1);

namespace App\Actions\Brainstorm;

use App\Models\Brainstorm;
use App\Models\Spec;
use Illuminate\Support\Facades\DB;

final readonly class CreateSpecFromIdea
{
    public function handle(Brainstorm $brainstorm, int $ideaIndex): Spec
    {
        return DB::transaction(function () use ($brainstorm, $ideaIndex) {
            $ideas = $brainstorm->ideas;

            if (! isset($ideas[$ideaIndex])) {
                abort(404, 'Idea not found');
            }

            $idea = $ideas[$ideaIndex];

            return Spec::create([
                'project_id' => $brainstorm->project_id,
                'title' => $idea['title'],
                'content' => $idea['description'],
                'generated_from_idea' => json_encode($idea),
            ]);
        });
    }
}
