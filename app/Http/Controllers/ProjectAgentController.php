<?php

namespace App\Http\Controllers;

use App\Actions\CheckAgentAuthenticated;
use App\Actions\CheckAgentInstalled;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ProjectAgentController extends Controller
{
    /**
     * Display the agent management page for a project.
     */
    public function index(
        Project $project,
        CheckAgentInstalled $checkInstalled,
        CheckAgentAuthenticated $checkAuthenticated
    ): Response {
        return Inertia::render('projects/agent', [
            'project' => $project,
            'agentInstalled' => Inertia::defer(fn () => $checkInstalled->handle()),
            'agentAuthenticated' => Inertia::defer(fn () => $checkAuthenticated->handle()),
        ]);
    }
}
