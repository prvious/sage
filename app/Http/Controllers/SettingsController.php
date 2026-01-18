<?php

namespace App\Http\Controllers;

use App\Actions\Server\GetServerStatus;
use App\Actions\Server\TestServerConnection;
use App\Actions\Settings\UpdateProjectSettings;
use App\Http\Requests\UpdateProjectSettingsRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the project settings page.
     */
    public function index(Project $project, GetServerStatus $getServerStatus): Response
    {
        return Inertia::render('projects/settings', [
            'project' => $project,
            'serverStatus' => $getServerStatus->handle($project),
        ]);
    }

    /**
     * Update project settings.
     */
    public function update(
        UpdateProjectSettingsRequest $request,
        Project $project,
        UpdateProjectSettings $updateProjectSettings
    ): RedirectResponse {
        $updateProjectSettings->handle($project, $request->validated());

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Test server connection.
     */
    public function testServer(
        Project $project,
        TestServerConnection $testServerConnection
    ): JsonResponse {
        $result = $testServerConnection->handle($project);

        return response()->json($result);
    }
}
