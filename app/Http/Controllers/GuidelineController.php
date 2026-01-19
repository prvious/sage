<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Guideline\AggregateGuidelines;
use App\Actions\Guideline\DeleteGuideline;
use App\Actions\Guideline\ListGuidelines;
use App\Actions\Guideline\ReadGuideline;
use App\Actions\Guideline\WriteGuideline;
use App\Http\Requests\StoreGuidelineRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GuidelineController extends Controller
{
    public function __construct(
        private readonly ListGuidelines $listGuidelines,
        private readonly ReadGuideline $readGuideline,
        private readonly WriteGuideline $writeGuideline,
        private readonly DeleteGuideline $deleteGuideline,
        private readonly AggregateGuidelines $aggregateGuidelines,
    ) {}

    public function index(Project $project): Response
    {
        $guidelines = $this->listGuidelines->handle($project);

        return Inertia::render('projects/guidelines/index', [
            'files' => $guidelines,
            'project' => $project,
        ]);
    }

    public function create(Project $project): Response
    {
        return Inertia::render('projects/guidelines/create', [
            'project' => $project,
        ]);
    }

    public function store(StoreGuidelineRequest $request, Project $project): RedirectResponse
    {
        try {
            $this->writeGuideline->handle(
                $project,
                $request->validated('filename'),
                $request->validated('content')
            );

            return redirect()
                ->route('projects.guidelines.index', $project)
                ->with('success', 'Guideline created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Project $project, string $guideline): Response
    {
        try {
            $content = $this->readGuideline->handle($project, $guideline);

            return Inertia::render('projects/guidelines/show', [
                'project' => $project,
                'filename' => $guideline,
                'content' => $content,
            ]);
        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function edit(Project $project, string $guideline): Response
    {
        try {
            $content = $this->readGuideline->handle($project, $guideline);

            return Inertia::render('projects/guidelines/edit', [
                'project' => $project,
                'filename' => $guideline,
                'content' => $content,
            ]);
        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function update(StoreGuidelineRequest $request, Project $project, string $guideline): RedirectResponse
    {
        try {
            $this->writeGuideline->handle(
                $project,
                $guideline,
                $request->validated('content')
            );

            return redirect()
                ->route('projects.guidelines.index', $project)
                ->with('success', 'Guideline updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Project $project, string $guideline): RedirectResponse
    {
        try {
            $this->deleteGuideline->handle($project, $guideline);

            return redirect()
                ->route('projects.guidelines.index', $project)
                ->with('success', 'Guideline deleted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function aggregate(Project $project): RedirectResponse
    {
        try {
            $result = $this->aggregateGuidelines->handle($project);

            if ($result['success']) {
                return redirect()
                    ->back()
                    ->with('success', 'Guidelines aggregated successfully.')
                    ->with('output', $result['output']);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'Aggregation failed.'])
                ->with('output', $result['output']);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
