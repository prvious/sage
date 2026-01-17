<?php

namespace App\Http\Controllers;

use App\Jobs\Agent\RunAgent;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    /**
     * Display all running agents across all projects.
     */
    public function index(): Response
    {
        $runningAgents = Task::with(['project', 'worktree'])
            ->where('status', 'in_progress')
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function (Task $task) {
                return [
                    'id' => $task->id,
                    'project_id' => $task->project_id,
                    'project_name' => $task->project->name,
                    'worktree_id' => $task->worktree_id,
                    'worktree_name' => $task->worktree?->name,
                    'agent_type' => $task->agent_type,
                    'model' => $task->model,
                    'status' => $task->status,
                    'started_at' => $task->started_at,
                    'agent_output' => $task->agent_output,
                    'description' => $task->description,
                ];
            });

        return Inertia::render('agents/index', [
            'runningAgents' => $runningAgents,
        ]);
    }

    /**
     * Start an agent on a task.
     */
    public function start(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'model' => 'nullable|string',
        ]);

        RunAgent::dispatch($task, $validated['prompt'], [
            'model' => $validated['model'] ?? null,
        ]);

        return response()->json([
            'message' => 'Agent started successfully.',
            'task' => $task->fresh(),
        ]);
    }

    /**
     * Stop a running agent.
     */
    public function stop(Task $task): JsonResponse
    {
        $task->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Agent stopped successfully.',
            'task' => $task->fresh(),
        ]);
    }

    /**
     * Get agent output for a task.
     */
    public function output(Task $task): JsonResponse
    {
        return response()->json([
            'output' => $task->agent_output,
            'status' => $task->status,
        ]);
    }
}
