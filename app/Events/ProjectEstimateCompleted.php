<?php

namespace App\Events;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectEstimateCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Project $project) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('projects.'.$this->project->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'project' => ProjectResource::make($this->project->loadMissing(['features', 'estimate']))->resolve(),
        ];
    }
}
