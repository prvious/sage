<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Display the specified task with its output.
     */
    public function show(Task $task): Response
    {
        $task->load(['project', 'worktree', 'commits']);

        return Inertia::render('tasks/show', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'agent_type' => $task->agent_type,
                'model' => $task->model,
                'agent_output' => $task->agent_output,
                'started_at' => $task->started_at,
                'completed_at' => $task->completed_at,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'project' => $task->project ? [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                ] : null,
                'worktree' => $task->worktree ? [
                    'id' => $task->worktree->id,
                    'branch_name' => $task->worktree->branch_name,
                ] : null,
                'commits' => $task->commits->map(fn ($commit) => [
                    'sha' => $commit->sha,
                    'message' => $commit->message,
                    'author' => $commit->author,
                    'created_at' => $commit->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Auto-generate title from first line of description if not provided
        if (empty($data['title'])) {
            $firstLine = explode("\n", $data['description'])[0];
            $data['title'] = trim(substr($firstLine, 0, 100));
        }

        // Set default status to queued
        $data['status'] = \App\Enums\TaskStatus::Queued;

        $task = Task::create($data);

        return redirect()
            ->back()
            ->with('success', 'Task created successfully');
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->back()
            ->with('success', 'Task deleted successfully.');
    }
}
