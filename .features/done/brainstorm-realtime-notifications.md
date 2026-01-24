---
name: brainstorm-realtime-notifications
description: Add real-time WebSocket notifications using Laravel Reverb when brainstorm ideas are ready
depends_on: brainstorm-ai-generation
---

## Feature Description

Add real-time notifications to the brainstorming feature using Laravel Reverb and WebSockets. When a brainstorm job completes (or fails), broadcast an event that triggers a toast notification on the frontend. Users no longer need to manually refresh - they'll be notified instantly when ideas are ready.

## Implementation Plan

### Backend Components

**Install and Configure Reverb**:

1. Install Laravel Reverb:

```bash
php artisan install:broadcasting
```

2. Configure `.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

3. Start Reverb server:

```bash
php artisan reverb:start
```

**Events**:

- Create `app/Events/BrainstormCompleted.php` (implements `ShouldBroadcast`)

```php
class BrainstormCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Brainstorm $brainstorm) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('project.'.$this->brainstorm->project_id.'.brainstorm');
    }

    public function broadcastAs(): string
    {
        return 'brainstorm.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'brainstorm_id' => $this->brainstorm->id,
            'ideas_count' => count($this->brainstorm->ideas ?? []),
            'message' => '💡 New ideas are ready!',
        ];
    }
}
```

- Create `app/Events/BrainstormFailed.php` (implements `ShouldBroadcast`)

```php
class BrainstormFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Brainstorm $brainstorm,
        public string $error
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('project.'.$this->brainstorm->project_id.'.brainstorm');
    }

    public function broadcastAs(): string
    {
        return 'brainstorm.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'brainstorm_id' => $this->brainstorm->id,
            'error' => $this->error,
            'message' => 'Failed to generate ideas',
        ];
    }
}
```

**Update Job**:

Modify `GenerateBrainstormIdeas` to broadcast events:

```php
public function handle()
{
    // ... existing logic ...

    // After successful completion
    broadcast(new BrainstormCompleted($this->brainstorm));
}

public function failed(Throwable $exception)
{
    $this->brainstorm->update([
        'status' => 'failed',
        'error_message' => $exception->getMessage(),
    ]);

    broadcast(new BrainstormFailed($this->brainstorm, $exception->getMessage()));
}
```

**Broadcast Channel**:

Define channel in `routes/channels.php`:

```php
use App\Models\Project;

Broadcast::channel('project.{projectId}.brainstorm', function ($user, $projectId) {
    // Add proper authorization logic
    return Project::where('id', $projectId)->exists();
});
```

### Frontend Components

**Install Dependencies**:

```bash
pnpm add laravel-echo pusher-js sonner
```

**Configure Laravel Echo**:

Create `resources/js/echo.ts`:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

export default window.Echo;
```

Import in `resources/js/app.tsx`:

```typescript
import './echo';
```

**Update TypeScript Types**:

Add to `resources/js/types/index.d.ts`:

```typescript
import Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: Echo;
        Pusher: any;
    }
}
```

**Add Toaster Component**:

Update `resources/js/app.tsx` to include Toaster:

```typescript
import { Toaster } from 'sonner';

// In your App component
<Toaster position="top-right" richColors />
```

**Update Brainstorm Page**:

Modify `resources/js/pages/projects/brainstorm.tsx` to listen for events:

```typescript
import { useEffect } from 'react';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';

useEffect(() => {
    const channel = window.Echo.private(`project.${project.id}.brainstorm`);

    channel.listen('.brainstorm.completed', (event: any) => {
        toast.success(event.message, {
            description: `${event.ideas_count} ideas generated`,
            action: {
                label: 'View',
                onClick: () => router.visit(`/projects/${project.id}/brainstorm/${event.brainstorm_id}`),
            },
        });

        // Reload brainstorms list
        router.reload({ only: ['brainstorms'] });
    });

    channel.listen('.brainstorm.failed', (event: any) => {
        toast.error(event.message, {
            description: event.error,
        });

        router.reload({ only: ['brainstorms'] });
    });

    return () => {
        channel.stopListening('.brainstorm.completed');
        channel.stopListening('.brainstorm.failed');
    };
}, [project.id]);
```

**Remove Manual Refresh**:

Since events are now real-time, remove the manual refresh button from the UI.

### Styling

**Toast Styling**:

The sonner library provides beautiful toast notifications by default. Configure position and styling:

```typescript
<Toaster
    position="top-right"
    richColors
    closeButton
    duration={5000}
/>
```

## Acceptance Criteria

- [ ] Laravel Reverb is installed and configured
- [ ] Reverb server runs successfully (`php artisan reverb:start`)
- [ ] BrainstormCompleted event broadcasts on job success
- [ ] BrainstormFailed event broadcasts on job failure
- [ ] Broadcast channel is properly defined and authorized
- [ ] Laravel Echo is configured with Reverb
- [ ] Frontend connects to Reverb WebSocket server
- [ ] Toast notification displays when ideas are ready
- [ ] Toast notification displays on failure
- [ ] Toast includes action button to view brainstorm
- [ ] Brainstorms list updates automatically via Inertia reload
- [ ] WebSocket connection lifecycle is managed properly
- [ ] Manual refresh button is removed (no longer needed)
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Brainstorm/BrainstormEventsTest.php`

**Key test cases**:

- Test BrainstormCompleted event broadcasts on job success
- Test BrainstormFailed event broadcasts on job failure
- Test events broadcast on correct channel
- Test broadcast data includes required fields
- Test channel authorization works correctly

### Browser Tests

**Test file location**: `tests/Browser/Brainstorm/RealtimeNotificationsTest.php`

**Key test cases**:

- Test WebSocket connection establishes
- Test toast appears when brainstorm completes (mock event)
- Test toast appears on failure (mock event)
- Test clicking toast action navigates to brainstorm
- Test brainstorms list updates after event
- Test connection cleans up on page leave

**Note**: Browser tests for WebSockets may need mocking or a test Reverb server.

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Running Reverb in Development

Start Reverb server:

```bash
php artisan reverb:start
```

Run alongside your app and queue worker:

```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan queue:work

# Terminal 3
php artisan reverb:start
```

### Environment Variables

Add to `.env`:

```env
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Production Deployment

For production:

1. Use a process manager like Supervisor to keep Reverb running
2. Consider using Laravel Forge or a dedicated WebSocket service
3. Configure proper SSL/TLS for wss:// connections
4. Set up proper CORS if frontend is on different domain

### Testing WebSockets

For integration tests with WebSockets:

```php
use Illuminate\Support\Facades\Event;

Event::fake([BrainstormCompleted::class]);

// Dispatch job
GenerateBrainstormIdeas::dispatchSync($brainstorm);

// Assert event was broadcast
Event::assertDispatched(BrainstormCompleted::class);
```

### Fallback Handling

If WebSocket connection fails, the app should still work:

- User can still manually refresh
- Or implement polling as fallback

```typescript
const [isConnected, setIsConnected] = useState(true);

useEffect(() => {
    const channel = window.Echo.private(`project.${project.id}.brainstorm`);

    channel.error(() => {
        setIsConnected(false);
        // Show fallback UI or enable polling
    });

    // ... rest of implementation
}, []);
```

### Next Steps

After this feature is complete, the optional enhancement feature (`brainstorm-ui-enhancements`) will add:

- Advanced filtering and sorting
- Better card designs
- Export functionality
- Idea actions (create spec, add to tasks)
