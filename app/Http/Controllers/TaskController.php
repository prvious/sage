<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
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
