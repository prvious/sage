<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Brainstorm;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrainstormCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Brainstorm $brainstorm
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('project.'.$this->brainstorm->project_id.'.brainstorm');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'brainstorm.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'brainstorm_id' => $this->brainstorm->id,
            'ideas_count' => count($this->brainstorm->ideas ?? []),
            'message' => '💡 New ideas are ready!',
        ];
    }
}
