<?php

namespace App\Http\Controllers;

use App\Actions\ListDirectory;
use App\Actions\ValidateProject;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): Response
    {
        $query = Project::query()
            ->withCount(['worktrees', 'tasks']);

        // Apply search filter if present
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('base_url', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest()->get();

        return Inertia::render('projects/index', [
            'projects' => $projects,
            'search' => $search ?? '',
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request, ListDirectory $listDirectory): Response
    {
        $path = $request->query('path');

        // Get home directory if no path specified
        if (! $path) {
            $path = Env::get('HOME') ?? Env::get('USERPROFILE') ?? '/';
        }

        // Security: Prevent directory traversal
        if (str_contains($path, '..')) {
            $path = '/';
        }

        // List directory contents
        $directoryData = $listDirectory->handle($path);

        return Inertia::render('projects/create', [
            'directories' => $directoryData['directories'],
            'breadcrumbs' => $directoryData['breadcrumbs'],
            'currentPath' => $path,
            'homePath' => Env::get('HOME') ?? Env::get('USERPROFILE') ?? '/',
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request, ValidateProject $validateProject): RedirectResponse
    {
        $validateProject->handle($request->validated('path'));

        $data = $request->validated();
        $data['server_driver'] = 'artisan';

        $project = Project::create($data);

        return redirect()->route('projects.dashboard', $project)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('projects/edit', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return back()->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        if ($project->worktrees()->where('status', '!=', 'deleted')->exists()) {
            return back()->withErrors([
                'project' => 'Cannot delete project with active worktrees.',
            ]);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
