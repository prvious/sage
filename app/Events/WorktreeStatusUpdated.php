<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Worktree;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorktreeStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Worktree $worktree
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("projects.{$this->worktree->project_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'worktree' => [
                'id' => $this->worktree->id,
                'status' => $this->worktree->status,
                'branch_name' => $this->worktree->branch_name,
                'preview_url' => $this->worktree->preview_url,
                'error_message' => $this->worktree->error_message,
            ],
        ];
    }
}
