<?php

namespace App\Http\Controllers;

use App\Actions\ListDirectory;
use App\Actions\ValidateProject;
use App\Drivers\Server\Manager as ServerManager;
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
    public function index(): Response
    {
        $projects = Project::query()
            ->withCount(['worktrees', 'tasks'])
            ->latest()
            ->get();

        return Inertia::render('projects/index', [
            'projects' => $projects,
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
    public function store(StoreProjectRequest $request, ValidateProject $validateProject, ServerManager $serverManager): RedirectResponse
    {
        $validateProject->handle($request->validated('path'));

        $driver = $serverManager->driver($request->validated('server_driver'));
        if (! $driver->validate()) {
            return back()->withErrors([
                'server_driver' => "The {$request->validated('server_driver')} server is not available on this system.",
            ]);
        }

        $project = Project::create($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): Response
    {
        $project->load(['worktrees', 'tasks', 'specs']);

        return Inertia::render('projects/show', [
            'project' => $project,
        ]);
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
