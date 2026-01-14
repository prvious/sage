<?php

namespace App\Http\Controllers;

use App\Jobs\Agent\RunAgent;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
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
