<?php

use App\Models\Project;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('projects.{project}', function ($user, Project $project) {
    return $project->user_id === $user->id;
});
