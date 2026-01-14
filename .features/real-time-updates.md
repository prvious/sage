---
name: real-time-updates
description: WebSocket-based real-time updates using Laravel Reverb
depends_on: database-and-models
---

## Detailed Description

This feature implements real-time communication across the Sage dashboard using Laravel Reverb (WebSockets). It enables instant updates for task status changes, agent output streaming, worktree creation progress, and more without page refreshes.

### Key Capabilities

- Real-time agent terminal output streaming
- Task status updates across all clients
- Worktree creation progress updates
- Live Kanban board updates (drag-and-drop sync)
- Terminal command output streaming
- Presence channels (see who's viewing same project)
- Toast notifications for important events
- Connection status indicator
- Automatic reconnection on disconnect
- Fallback to polling if WebSockets unavailable

### Events to Broadcast

1. **TaskUpdated** - Task status/details changed
2. **AgentOutputReceived** - New line of agent output
3. **AgentStatusChanged** - Agent started/stopped/failed
4. **WorktreeStatusUpdated** - Worktree creation progress
5. **TerminalOutputReceived** - Terminal command output
6. **CommitCreated** - New commit detected
7. **SpecUpdated** - Spec file changed

### User Stories

1. As a developer, I want to see agent output in real-time as it works
2. As a developer, I want to see when other changes are made to tasks
3. As a developer, I want connection status visible
4. As a developer, I want automatic reconnection if I lose connection

## Detailed Implementation Plan

### Step 1: Install and Configure Laravel Reverb

Reverb should already be installed. Verify configuration:

```bash
php artisan reverb:install
```

Configure in `.env`:

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=sage-app
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Step 2: Configure Broadcasting

Update `config/broadcasting.php`:

```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT'),
        'scheme' => env('REVERB_SCHEME'),
    ],
],
```

### Step 3: Create Broadcast Events

Create all broadcast events:

```bash
php artisan make:event TaskUpdated --no-interaction
php artisan make:event AgentOutputReceived --no-interaction
php artisan make:event AgentStatusChanged --no-interaction
php artisan make:event WorktreeStatusUpdated --no-interaction
php artisan make:event TerminalOutputReceived --no-interaction
php artisan make:event CommitCreated --no-interaction
php artisan make:event SpecUpdated --no-interaction
```

### Step 4: Implement Broadcast Events

Example: **TaskUpdated Event**

```php
namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Task $task
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('project.' . $this->task->project_id);
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'task' => [
                'id' => $this->task->id,
                'status' => $this->task->status,
                'title' => $this->task->title,
                'updated_at' => $this->task->updated_at,
            ],
        ];
    }
}
```

Example: **AgentOutputReceived Event**

```php
class AgentOutputReceived implements ShouldBroadcast
{
    public function __construct(
        public int $taskId,
        public string $line,
        public string $type = 'stdout'
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('task.' . $this->taskId);
    }

    public function broadcastAs(): string
    {
        return 'agent.output';
    }
}
```

### Step 5: Set Up Frontend Echo Configuration

Install Laravel Echo and Pusher JS:

```bash
pnpm install laravel-echo pusher-js
```

Configure in `resources/js/bootstrap.ts`:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

Add to `.env`:

```env
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Step 6: Create Echo Hook for React

Create reusable hook:

```typescript
// resources/js/hooks/useEcho.ts

import { useEffect } from 'react';
import Echo from 'laravel-echo';

export const useEcho = (channel: string, event: string, callback: (data: any) => void) => {
    useEffect(() => {
        const echo = window.Echo.channel(channel);

        echo.listen(event, callback);

        return () => {
            echo.stopListening(event);
            window.Echo.leaveChannel(channel);
        };
    }, [channel, event, callback]);
};
```

Usage in components:

```typescript
import { useEcho } from '@/hooks/useEcho'

const TaskDetails = ({ task }) => {
    useEcho(`task.${task.id}`, 'agent.output', (data) => {
        appendToTerminal(data.line)
    })

    return <div>...</div>
}
```

### Step 7: Create Connection Status Component

```typescript
// resources/js/Components/ConnectionStatus.tsx

import { useEffect, useState } from 'react'
import { Badge } from '@/Components/ui/badge'

export const ConnectionStatus = () => {
    const [status, setStatus] = useState<'connected' | 'disconnected' | 'connecting'>('connecting')

    useEffect(() => {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            setStatus('connected')
        })

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            setStatus('disconnected')
        })

        window.Echo.connector.pusher.connection.bind('connecting', () => {
            setStatus('connecting')
        })
    }, [])

    return (
        <Badge variant={status === 'connected' ? 'success' : 'destructive'}>
            {status === 'connected' && '● Connected'}
            {status === 'disconnected' && '○ Disconnected'}
            {status === 'connecting' && '◔ Connecting...'}
        </Badge>
    )
}
```

Display in header or footer.

### Step 8: Implement Automatic Reconnection

Configure Pusher with auto-reconnect:

```typescript
window.Echo = new Echo({
    // ... other config
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    activityTimeout: 30000,
    pongTimeout: 10000,
});
```

Handle reconnection in app:

```typescript
window.Echo.connector.pusher.connection.bind('disconnected', () => {
    // Show notification
    toast.error('Connection lost. Reconnecting...');
});

window.Echo.connector.pusher.connection.bind('connected', () => {
    toast.success('Connected');
});
```

### Step 9: Implement Private Channels (Future)

For authenticated users:

```bash
php artisan make:channel ProjectChannel --no-interaction
```

Define authorization logic:

```php
// routes/channels.php
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    return $user->canAccessProject($projectId);
});
```

Use private channel:

```php
public function broadcastOn(): PrivateChannel
{
    return new PrivateChannel('project.' . $this->task->project_id);
}
```

### Step 10: Implement Presence Channels (Optional)

Show who's viewing the same project:

```php
Broadcast::channel('project.{projectId}.presence', function ($user, $projectId) {
    if ($user->canAccessProject($projectId)) {
        return ['id' => $user->id, 'name' => $user->name];
    }
});
```

Listen for presence events:

```typescript
window.Echo.join(`project.${projectId}.presence`)
    .here((users) => {
        console.log('Users here:', users);
    })
    .joining((user) => {
        console.log('User joined:', user);
    })
    .leaving((user) => {
        console.log('User left:', user);
    });
```

### Step 11: Implement Toast Notifications

Install sonner for toast notifications:

```bash
pnpm install sonner
```

Create notification system:

```typescript
// resources/js/Components/Notifications.tsx

import { Toaster, toast } from 'sonner'

export const Notifications = () => {
    return <Toaster position="top-right" />
}

// Listen for important events and show toasts
useEcho('project.1', 'task.updated', (data) => {
    toast.success(`Task "${data.task.title}" was updated`)
})
```

### Step 12: Optimize Event Broadcasting

Use `ShouldBroadcastNow` for critical events:

```php
class AgentOutputReceived implements ShouldBroadcastNow
{
    // This bypasses the queue for immediate broadcasting
}
```

For non-critical events, use regular `ShouldBroadcast` (queued).

### Step 13: Implement Polling Fallback

For environments where WebSockets aren't available:

```typescript
const [isWebSocketAvailable, setIsWebSocketAvailable] = useState(true);

useEffect(() => {
    const checkConnection = () => {
        if (window.Echo.connector.pusher.connection.state === 'unavailable') {
            setIsWebSocketAvailable(false);
            // Start polling
            startPolling();
        }
    };

    const timer = setTimeout(checkConnection, 5000);
    return () => clearTimeout(timer);
}, []);

const startPolling = () => {
    const interval = setInterval(() => {
        // Fetch updates via HTTP
        fetchUpdates();
    }, 5000);

    return () => clearInterval(interval);
};
```

### Step 14: Create Broadcasting Test Command

```bash
php artisan make:command TestBroadcastingCommand --no-interaction
```

Command: `php artisan sage:test-broadcast`

- Send test events
- Verify they're received
- Check connection status

### Step 15: Monitor Broadcasting Performance

Add logging for broadcast events:

```php
public function handle()
{
    Log::channel('broadcasting')->info('Broadcasting event', [
        'event' => class_basename($this),
        'channel' => $this->broadcastOn()->name,
        'timestamp' => now(),
    ]);
}
```

### Step 16: Create Feature Tests

Test coverage:

- `it('broadcasts task updated event')`
- `it('broadcasts agent output event')`
- `it('broadcasts worktree status event')`
- `it('handles connection errors gracefully')`

Use `Event::fake()` and `Event::assertDispatched()`.

### Step 17: Create Browser Tests

E2E test coverage:

- `it('receives real-time task updates')`
- `it('displays agent output in real-time')`
- `it('shows connection status correctly')`
- `it('displays toast notifications on events')`

### Step 18: Document WebSocket Setup

Create `docs/websockets.md`:

- How to start Reverb server
- Firewall configuration
- Troubleshooting connection issues
- Performance tuning

### Step 19: Start Reverb in Production

Add to process management (Supervisor, systemd):

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

For FrankenPHP distribution, include Reverb start in binary.

### Step 20: Format Code

```bash
vendor/bin/pint --dirty
pnpm run format
```
