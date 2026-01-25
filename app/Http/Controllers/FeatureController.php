<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeatureRequest;
use App\Jobs\Feature\GenerateFeatureWorkflow;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class FeatureController extends Controller
{
    /**
     * Store a new feature workflow.
     */
    public function store(StoreFeatureRequest $request, Project $project): RedirectResponse
    {
        // Dispatch job to generate feature in background
        GenerateFeatureWorkflow::dispatch(
            $project->id,
            $request->validated('description')
        );

        return back()->with('success', 'Generating feature in background. You\'ll be notified when ready!');
    }
}
