<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorktreeRequest;
use App\Models\Project;
use App\Models\Worktree;
use App\Services\WorktreeService;
use Inertia\Inertia;
use Inertia\Response;

class WorktreeController extends Controller
{
    public function __construct(
        private readonly WorktreeService $worktreeService
    ) {}

    public function index(Project $project): Response
    {
        $worktrees = $project->worktrees()
            ->latest()
            ->get();

        return Inertia::render('Worktrees/Index', [
            'project' => $project,
            'worktrees' => $worktrees,
        ]);
    }

    public function create(Project $project): Response
    {
        return Inertia::render('Worktrees/Create', [
            'project' => $project,
        ]);
    }

    public function store(StoreWorktreeRequest $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        // Check if worktree already exists for this branch
        $exists = $project->worktrees()
            ->where('branch_name', $request->validated('branch_name'))
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'branch_name' => 'A worktree already exists for this branch.',
            ]);
        }

        $worktree = $this->worktreeService->create(
            $project,
            $request->validated('branch_name'),
            $request->validated('create_branch', false),
            $request->validated('database_isolation', 'separate')
        );

        return redirect()->route('projects.worktrees.show', [$project, $worktree])
            ->with('success', 'Worktree is being created. This may take a few minutes.');
    }

    public function show(Project $project, Worktree $worktree): Response
    {
        return Inertia::render('Worktrees/Show', [
            'project' => $project,
            'worktree' => $worktree,
        ]);
    }

    public function destroy(Project $project, Worktree $worktree): \Illuminate\Http\RedirectResponse
    {
        $this->worktreeService->delete($worktree);

        return redirect()->route('projects.worktrees.index', $project)
            ->with('success', 'Worktree deleted successfully.');
    }
}
