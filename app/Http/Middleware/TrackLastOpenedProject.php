<?php

namespace App\Http\Middleware;

use App\Actions\UpdateLastOpenedProject;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastOpenedProject
{
    public function __construct(protected UpdateLastOpenedProject $updateLastOpenedProject) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the route has a 'project' parameter
        $project = $request->route('project');

        if ($project) {
            // Handle both model instances and IDs
            $projectId = is_object($project) ? $project->id : (int) $project;
            $this->updateLastOpenedProject->handle($projectId);
        }

        return $next($request);
    }
}
