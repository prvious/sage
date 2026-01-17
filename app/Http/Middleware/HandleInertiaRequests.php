<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $project = $request->route('project');

        // If we have a project in the route, store it as the last selected project
        if ($project instanceof Project) {
            $request->session()->put('last_selected_project_id', $project->id);
        }

        // If no project in route, try to load the last selected project from session
        $selectedProject = null;
        if ($project instanceof Project) {
            $selectedProject = [
                'id' => $project->id,
                'name' => $project->name,
                'path' => $project->path,
            ];
        } elseif ($lastProjectId = $request->session()->get('last_selected_project_id')) {
            $lastProject = Project::find($lastProjectId);
            if ($lastProject) {
                $selectedProject = [
                    'id' => $lastProject->id,
                    'name' => $lastProject->name,
                    'path' => $lastProject->path,
                ];
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'projects' => Project::query()->select(['id', 'name', 'path'])->get(),
            'selectedProject' => $selectedProject,
        ];
    }
}
