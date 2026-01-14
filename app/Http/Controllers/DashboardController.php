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
     * Display the dashboard with projects and tasks.
     */
    public function __invoke(): Response
    {
        $projects = Project::select('id', 'name', 'path')
            ->orderBy('name')
            ->get();

        $tasks = Task::with(['worktree.project', 'commits'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('status');

        return Inertia::render('dashboard/index', [
            'projects' => ProjectResource::collection($projects),
            'tasks' => [
                'idea' => TaskResource::collection($tasks->get('idea', collect())),
                'in_progress' => TaskResource::collection($tasks->get('in_progress', collect())),
                'review' => TaskResource::collection($tasks->get('review', collect())),
                'done' => TaskResource::collection($tasks->get('done', collect())),
            ],
        ]);
    }
}
