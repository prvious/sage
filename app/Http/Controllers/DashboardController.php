<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Display the project-specific dashboard with tasks.
     */
    public function show(Project $project): Response
    {
        $tasks = Task::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('status');

        return Inertia::render('projects/dashboard', [
            'project' => new ProjectResource($project),
            'tasks' => [
                'queued' => TaskResource::collection($tasks->get('queued', collect())),
                'in_progress' => TaskResource::collection($tasks->get('in_progress', collect())),
                'waiting_review' => TaskResource::collection($tasks->get('waiting_review', collect())),
                'done' => TaskResource::collection($tasks->get('done', collect())),
            ],
        ]);
    }
}
