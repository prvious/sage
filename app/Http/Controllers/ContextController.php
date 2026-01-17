<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Context\AggregateContextFiles;
use App\Actions\Context\DeleteContextFile;
use App\Actions\Context\ListContextFiles;
use App\Actions\Context\ReadContextFile;
use App\Actions\Context\WriteContextFile;
use App\Http\Requests\StoreContextFileRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContextController extends Controller
{
    public function __construct(
        private readonly ListContextFiles $listContextFiles,
        private readonly ReadContextFile $readContextFile,
        private readonly WriteContextFile $writeContextFile,
        private readonly DeleteContextFile $deleteContextFile,
        private readonly AggregateContextFiles $aggregateContextFiles,
    ) {}

    public function index(Project $project): Response
    {
        $files = $this->listContextFiles->handle($project);

        return Inertia::render('projects/context/index', [
            'files' => $files,
            'project' => $project,
        ]);
    }

    public function create(Project $project): Response
    {
        return Inertia::render('projects/context/create', [
            'project' => $project,
        ]);
    }

    public function store(StoreContextFileRequest $request, Project $project): RedirectResponse
    {
        try {
            $this->writeContextFile->handle(
                $project,
                $request->validated('filename'),
                $request->validated('content')
            );

            return redirect()
                ->route('projects.context.index', $project)
                ->with('success', 'Context file created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Project $project, string $file): Response
    {
        try {
            $content = $this->readContextFile->handle($project, $file);

            return Inertia::render('projects/context/show', [
                'project' => $project,
                'filename' => $file,
                'content' => $content,
            ]);
        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function edit(Project $project, string $file): Response
    {
        try {
            $content = $this->readContextFile->handle($project, $file);

            return Inertia::render('projects/context/edit', [
                'project' => $project,
                'filename' => $file,
                'content' => $content,
            ]);
        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function update(StoreContextFileRequest $request, Project $project, string $file): RedirectResponse
    {
        try {
            $this->writeContextFile->handle(
                $project,
                $file,
                $request->validated('content')
            );

            return redirect()
                ->route('projects.context.index', $project)
                ->with('success', 'Context file updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Project $project, string $file): RedirectResponse
    {
        try {
            $this->deleteContextFile->handle($project, $file);

            return redirect()
                ->route('projects.context.index', $project)
                ->with('success', 'Context file deleted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function aggregate(Project $project): RedirectResponse
    {
        try {
            $result = $this->aggregateContextFiles->handle($project);

            if ($result['success']) {
                return redirect()
                    ->back()
                    ->with('success', 'Context files aggregated successfully.')
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
