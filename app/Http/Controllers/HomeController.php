<?php

namespace App\Http\Controllers;

use App\Actions\GetLastOpenedProject;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    public function __construct(protected GetLastOpenedProject $getLastOpenedProject) {}

    public function index(): RedirectResponse
    {
        try {
            $projectId = $this->getLastOpenedProject->handle();

            if ($projectId && Project::where('id', $projectId)->exists()) {
                return redirect()->route('projects.dashboard', $projectId);
            }
        } catch (\Exception $e) {
            // Gracefully handle cache failures
            report($e);
        }

        return redirect()->route('projects.index');
    }
}
