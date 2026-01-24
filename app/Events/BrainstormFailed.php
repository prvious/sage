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

class BrainstormFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Brainstorm $brainstorm,
        public string $error
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
        return 'brainstorm.failed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'brainstorm_id' => $this->brainstorm->id,
            'error' => $this->error,
            'message' => 'Failed to generate ideas: '.$this->error,
        ];
    }
}
