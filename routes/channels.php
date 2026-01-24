<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('project.{projectId}.brainstorm', function (User $user, int $projectId) {
    // Verify project exists - in a single-user app, all authenticated users have access
    return \App\Models\Project::where('id', $projectId)->exists();
});
