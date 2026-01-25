---
name: queue-feature-generation-workflow
description: Move spec generation and task creation to background queue with real-time progress updates
depends_on: add-feature-workflow-button
---

## Feature Description

Move the time-intensive spec generation and task creation operations to Laravel's queue system. Since these AI-powered operations can take several minutes, they should run in the background to prevent request timeouts and provide a better user experience.

**Current Flow** (synchronous, blocks):

1. User submits feature description
2. **[BLOCKS]** Generate spec via AI (30-120 seconds)
3. **[BLOCKS]** Parse spec and create tasks (5-30 seconds)
4. Return response with tasks

**New Flow** (asynchronous, non-blocking):

1. User submits feature description
2. Immediately return success toast: "Generating feature in background..."
3. **[QUEUE]** Job: Generate spec via AI
4. **[QUEUE]** Job: Parse spec and create tasks
5. **[WEBSOCKET]** Broadcast completion event with task count
6. Frontend shows real-time toast: "Feature created! X tasks added"
7. Kanban board auto-updates with new tasks

## Implementation Plan

### Backend Components

**Jobs** (following Laravel queue best practices):

- Create `app/Jobs/Feature/GenerateFeatureWorkflow.php`
    - Dispatched when user submits feature description
    - Calls `GenerateSpecFromDescription` action
    - Calls `CreateTasksFromSpec` action
    - Broadcasts `FeatureGeneratedEvent` on completion
    - Broadcasts `FeatureGenerationFailedEvent` on failure
    - Implements `ShouldQueue` interface
    - Uses queue: `features`
    - Timeout: 300 seconds (5 minutes)
    - Retries: 2 times on failure

**Events** (for real-time updates):

- Create `app/Events/Feature/FeatureGenerated.php`
    - Broadcasts on channel: `project.{projectId}.features`
    - Event data:
        - `feature_id` - ID of created feature/spec
        - `task_count` - Number of tasks created
        - `message` - Success message
        - `project_id` - Project ID

- Create `app/Events/Feature/FeatureGenerationFailed.php`
    - Broadcasts on channel: `project.{projectId}.features`
    - Event data:
        - `error` - Error message
        - `description` - Original feature description
        - `project_id` - Project ID

**Controllers** (modify existing):

- Update `App\Http\Controllers\FeatureController@store`:
    - Validate request
    - Dispatch `GenerateFeatureWorkflow` job
    - Return immediate response with success message
    - Do NOT wait for job completion
    - Response structure:
        ```json
        {
            "message": "Generating feature in background. You'll be notified when ready!",
            "status": "processing"
        }
        ```

**Actions** (already exist from previous feature):

- `App\Actions\Feature\GenerateSpecFromDescription` - No changes needed
- `App\Actions\Feature\CreateTasksFromSpec` - No changes needed

**Form Requests** (already exists):

- `App\Http\Requests\StoreFeatureRequest` - No changes needed

**Routes** (already exists):

- `POST /projects/{project}/features` - No changes needed

**Database changes**:

- Create migration: `add_status_to_specs_table`
    - Add `status` column (string, nullable) to track: 'pending', 'processing', 'completed', 'failed'
    - Add `processing_started_at` timestamp (nullable)
    - Add `processing_completed_at` timestamp (nullable)
    - Add `error_message` text (nullable) for failure details

### Frontend Components

**Modified Components**:

- Update `resources/js/components/feature/add-feature-dialog.tsx`:
    - On successful submission, show toast immediately
    - Close dialog immediately (don't wait for tasks)
    - Toast message: "Generating feature in background. You'll be notified when ready!"
    - Use `sonner` toast library (already used in project)

**Event Listeners**:

- Add to `resources/js/pages/projects/dashboard.tsx`:
    - Listen for `feature.generated` event on `project.{projectId}.features` channel
    - On event received:
        - Show success toast with task count
        - Reload page data to display new tasks
        - Use Inertia's `router.reload({ only: ['tasks'] })`

    - Listen for `feature.generation.failed` event
    - On event received:
        - Show error toast with failure message
        - Optionally offer retry option

**WebSocket Integration**:

- Use existing Laravel Echo setup (`@laravel/echo-react`)
- Use existing Reverb broadcaster
- Example listener code:
    ```tsx
    useEcho<FeatureGeneratedEvent>(`project.${project.id}.features`, 'feature.generated', (event) => {
        toast.success('Feature created!', {
            description: `${event.task_count} tasks added to your board`,
        });
        router.reload({ only: ['tasks'] });
    });
    ```

**Styling**:

- Use existing Shadcn toast component (`sonner`)
- Follow existing toast patterns from brainstorm feature
- Dark mode support via existing toast styles

### Queue Configuration

**Queue Worker**:

- Dedicated queue for feature generation: `features`
- Configure in `config/queue.php` (if needed)
- Workers should run: `php artisan queue:work --queue=features`

**Job Properties**:

```php
public $queue = 'features';
public $timeout = 300; // 5 minutes
public $tries = 2;
public $backoff = 30; // Wait 30 seconds before retry
```

**Failed Jobs**:

- Laravel's built-in failed jobs table handles failures
- Failed jobs can be retried via: `php artisan queue:retry all`

## Acceptance Criteria

- [ ] User submits feature description and sees immediate success toast
- [ ] Dialog closes immediately without waiting for generation
- [ ] Spec generation runs in background queue
- [ ] Task creation runs in background queue
- [ ] Real-time event broadcasts when generation completes
- [ ] Success toast shows actual task count created
- [ ] Kanban board auto-updates with new tasks after generation
- [ ] Error events broadcast if generation fails
- [ ] Error toast displays failure reason
- [ ] `specs` table tracks processing status and timestamps
- [ ] Queue worker processes jobs successfully
- [ ] Job retries on transient failures
- [ ] Request does not timeout during long AI operations
- [ ] Multiple users can submit features simultaneously
- [ ] All tests pass
- [ ] Code formatted with Pint and Prettier

## Testing Strategy

### Unit Tests

**Test file**: `tests/Unit/Jobs/Feature/GenerateFeatureWorkflowTest.php`

- Test job dispatches successfully
- Test job calls GenerateSpecFromDescription action
- Test job calls CreateTasksFromSpec action
- Test job broadcasts FeatureGenerated event on success
- Test job broadcasts FeatureGenerationFailed event on failure
- Test job updates spec status to 'processing' when started
- Test job updates spec status to 'completed' when finished
- Test job updates spec status to 'failed' on error
- Test job stores error message on failure
- Test job retries on transient failures

**Test file**: `tests/Unit/Events/Feature/FeatureGeneratedTest.php`

- Test event broadcasts on correct channel
- Test event includes correct data structure
- Test event can be serialized/unserialized

**Test file**: `tests/Unit/Events/Feature/FeatureGenerationFailedTest.php`

- Test error event broadcasts on correct channel
- Test error event includes error message
- Test error event includes original description

### Feature Tests

**Test file**: `tests/Feature/Feature/QueuedFeatureWorkflowTest.php`

- Test POST to `/projects/{project}/features` dispatches job
- Test POST returns immediate success response
- Test POST does not wait for job completion
- Test job processes and creates spec
- Test job creates tasks from spec
- Test event broadcasts after job completion
- Test validation still works before queueing
- Test concurrent feature submissions work correctly
- Test failed job broadcasts error event
- Test spec status transitions: pending → processing → completed

### Browser Tests

**Test file**: `tests/Browser/Feature/QueuedFeatureWorkflowTest.php`

- Test submitting feature shows immediate toast
- Test dialog closes immediately after submission
- Test success toast appears when generation completes (mock WebSocket)
- Test new tasks appear on kanban after generation
- Test error toast appears if generation fails
- Test multiple submissions work without blocking UI
- Test user can continue using app while generation runs

### Integration Tests

**Test file**: `tests/Feature/Feature/FeatureGenerationEventsTest.php`

- Test complete flow: submit → queue → process → broadcast → receive
- Test WebSocket event delivery
- Test Inertia reload after event received
- Test failed generation event delivery

## Code Formatting

**PHP**:

```bash
vendor/bin/pint
```

**TypeScript/React**:

```bash
pnpm run format
```

## Additional Notes

### Queue Worker Deployment

**Local Development**:

```bash
php artisan queue:work --queue=features
```

**Production**:

- Use Laravel Horizon for queue monitoring (optional)
- Use Supervisor to keep queue workers running
- Configure workers in deployment scripts

### Error Handling Strategy

**Transient Errors** (retry):

- Network timeouts to Anthropic API
- Temporary API rate limits
- Database connection issues

**Permanent Errors** (fail immediately):

- Invalid API key
- Malformed response from AI
- Validation failures

**User Notification**:

- All failures broadcast via WebSocket
- Error messages should be user-friendly
- Provide actionable feedback when possible

### Performance Considerations

**Queue Throughput**:

- Each feature generation takes 30-120 seconds
- With 2 workers, can process ~60-120 features/hour
- Consider scaling workers based on usage

**Database Updates**:

- Update spec status at key points: pending → processing → completed/failed
- Use timestamps to track processing duration
- Useful for debugging and analytics

### Real-time Update Pattern

This feature establishes a reusable pattern for long-running operations:

1. Immediate user feedback (toast)
2. Background job processing
3. WebSocket event broadcast
4. Frontend auto-update

Can be reused for:

- Brainstorm generation (already uses this pattern)
- Agent execution
- Bulk task operations
- Report generation

### Migration Strategy

**Existing Code**:

- Previous `add-feature-workflow-button` feature created synchronous flow
- This feature converts it to asynchronous
- No breaking changes to API contract
- Frontend changes are backward compatible

**Rollout**:

1. Add specs table columns (migration)
2. Deploy job and event classes
3. Update controller to dispatch job
4. Update frontend to handle events
5. Monitor queue worker performance

### Testing WebSocket Events

**In Tests**:

- Use `Event::fake()` to test event dispatch
- Use `Queue::fake()` to test job dispatch
- Mock WebSocket events in browser tests
- Use Laravel's built-in event broadcasting test helpers

**Manual Testing**:

- Start queue worker: `php artisan queue:work`
- Start Reverb server: `php artisan reverb:start`
- Submit feature via UI
- Watch queue worker logs
- Verify toast appears after completion

### Future Enhancements

**Progress Updates**:

- Broadcast intermediate progress: "Generating spec..." → "Creating tasks..."
- Show progress bar in UI
- Estimate time remaining

**Cancellation**:

- Allow user to cancel in-progress generation
- Add cancel button to toast notification
- Handle job termination gracefully

**History**:

- Track all feature generation attempts
- Show processing status in UI
- Allow viewing failed generations

### Integration with Existing Features

**Brainstorm**:

- Already uses similar async pattern
- Can share event listener patterns
- Consistent user experience

**Tasks**:

- Tasks created by this feature appear same as manual tasks
- No special handling needed
- Can be edited/deleted normally

**Specs**:

- Specs created have `generated_from_idea = true`
- Status tracking added for queue processing
- Can be viewed/managed normally
