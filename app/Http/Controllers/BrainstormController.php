<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Brainstorm\CreateSpecFromIdea;
use App\Actions\Brainstorm\ExportIdeas;
use App\Http\Requests\StoreBrainstormRequest;
use App\Jobs\GenerateBrainstormIdeas;
use App\Models\Brainstorm;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $brainstorm = Brainstorm::create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'user_context' => $request->input('user_context'),
            'status' => 'pending',
        ]);

        // Dispatch job to generate ideas in background
        GenerateBrainstormIdeas::dispatch($brainstorm);

        return redirect()->route('projects.brainstorm.index', $project)
            ->with('success', 'Brainstorm started! Ideas will be generated in the background.');
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

    /**
     * Export brainstorm ideas as markdown.
     */
    public function export(ExportIdeas $exportIdeas, Project $project, Brainstorm $brainstorm): StreamedResponse
    {
        $markdown = $exportIdeas->handle($brainstorm);

        $fileName = 'brainstorm-'.str($project->name)->slug().'-'.now()->format('Y-m-d').'.md';

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, $fileName, [
            'Content-Type' => 'text/markdown',
        ]);
    }

    /**
     * Create a spec from a brainstorm idea.
     */
    public function createSpec(CreateSpecFromIdea $createSpecFromIdea, Project $project, Brainstorm $brainstorm, int $index): RedirectResponse
    {
        $spec = $createSpecFromIdea->handle($brainstorm, $index);

        return redirect()
            ->route('projects.specs.show', [$project, $spec])
            ->with('success', 'Spec created from idea!');
    }
}
