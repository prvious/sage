<?php

namespace App\Http\Controllers;

use App\Actions\Spec\GenerateTaskFromSpec;
use App\Http\Resources\SpecResource;
use App\Models\Project;
use App\Models\Spec;
use App\Services\SpecGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpecController extends Controller
{
    public function __construct(
        protected SpecGeneratorService $specGenerator,
        protected GenerateTaskFromSpec $generateTaskFromSpec
    ) {}

    /**
     * Display a listing of specs for a project.
     */
    public function index(Project $project): Response
    {
        $specs = $project->specs()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('projects/specs/index', [
            'project' => $project->only(['id', 'name', 'path']),
            'specs' => SpecResource::collection($specs),
        ]);
    }

    /**
     * Show the form for creating a new spec.
     */
    public function create(Project $project): Response
    {
        return Inertia::render('projects/specs/create', [
            'project' => $project->only(['id', 'name', 'path']),
        ]);
    }

    /**
     * Generate a spec from an idea using AI.
     */
    public function generate(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'idea' => 'required|string|min:10|max:5000',
            'type' => 'required|in:feature,api,refactor,bug',
        ]);

        try {
            $content = $this->specGenerator->generate(
                $validated['idea'],
                $validated['type']
            );

            return response()->json([
                'success' => true,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created spec.
     */
    public function store(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $spec = $project->specs()->create($validated);

        return redirect()->route('projects.specs.show', [$project, $spec])->with('success', 'Spec created successfully.');
    }

    /**
     * Display the specified spec.
     */
    public function show(Project $project, Spec $spec): Response
    {
        abort_if($spec->project_id !== $project->id, 404);

        return Inertia::render('projects/specs/show', [
            'project' => $project->only(['id', 'name', 'path']),
            'spec' => new SpecResource($spec),
        ]);
    }

    /**
     * Show the form for editing the specified spec.
     */
    public function edit(Project $project, Spec $spec): Response
    {
        abort_if($spec->project_id !== $project->id, 404);

        return Inertia::render('projects/specs/edit', [
            'project' => $project->only(['id', 'name', 'path']),
            'spec' => new SpecResource($spec),
        ]);
    }

    /**
     * Update the specified spec.
     */
    public function update(Request $request, Project $project, Spec $spec): \Illuminate\Http\RedirectResponse
    {
        abort_if($spec->project_id !== $project->id, 404);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $spec->update($validated);

        return redirect()->route('projects.specs.show', [$project, $spec])->with('success', 'Spec updated successfully.');
    }

    /**
     * Remove the specified spec.
     */
    public function destroy(Project $project, Spec $spec): \Illuminate\Http\RedirectResponse
    {
        abort_if($spec->project_id !== $project->id, 404);

        $spec->delete();

        return redirect()->route('projects.specs.index', $project)->with('success', 'Spec deleted successfully.');
    }

    /**
     * Refine an existing spec with feedback.
     */
    public function refine(Request $request, Project $project, Spec $spec): JsonResponse
    {
        abort_if($spec->project_id !== $project->id, 404);

        $validated = $request->validate([
            'feedback' => 'required|string|min:10|max:2000',
        ]);

        try {
            $refinedContent = $this->specGenerator->refine(
                $spec->content,
                $validated['feedback']
            );

            return response()->json([
                'success' => true,
                'content' => $refinedContent,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pre-filled task data from a spec.
     */
    public function previewTask(Project $project, Spec $spec): JsonResponse
    {
        abort_if($spec->project_id !== $project->id, 404);

        return response()->json([
            'title' => $this->generateTaskFromSpec->generateTitle($spec),
            'description' => $this->generateTaskFromSpec->generateDescription($spec),
        ]);
    }

    /**
     * Create a task from a spec.
     */
    public function createTask(Request $request, Project $project, Spec $spec): RedirectResponse
    {
        abort_if($spec->project_id !== $project->id, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'worktree_id' => ['nullable', 'exists:worktrees,id'],
        ]);

        $task = $this->generateTaskFromSpec->handle($spec, [
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'],
            'worktree_id' => $validated['worktree_id'] ?? null,
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Task created from spec successfully.');
    }
}
