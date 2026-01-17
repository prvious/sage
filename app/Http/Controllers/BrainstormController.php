<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrainstormRequest;
use App\Models\Brainstorm;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class BrainstormController extends Controller
{
    /**
     * Display the brainstorm page for the project.
     */
    public function index(Project $project): Response
    {
        $brainstorms = Brainstorm::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('projects/brainstorm', [
            'project' => $project,
            'brainstorms' => $brainstorms,
        ]);
    }

    /**
     * Store a new brainstorm session.
     */
    public function store(StoreBrainstormRequest $request, Project $project): RedirectResponse
    {
        Brainstorm::create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'user_context' => $request->input('user_context'),
            'status' => 'pending',
        ]);

        return redirect()->route('projects.brainstorm.index', $project)
            ->with('success', 'Brainstorm session created successfully.');
    }

    /**
     * Display a specific brainstorm session.
     */
    public function show(Project $project, Brainstorm $brainstorm): Response
    {
        return Inertia::render('projects/brainstorm-show', [
            'project' => $project,
            'brainstorm' => $brainstorm,
        ]);
    }
}
