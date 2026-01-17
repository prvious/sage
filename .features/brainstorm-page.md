---
name: brainstorm-page
description: AI-powered brainstorming page that generates project-specific feature ideas with real-time notifications
depends_on: project-context-sidebar
---

## Feature Description

Create an AI-powered "Brainstorm" page that helps users generate feature ideas for their project. Users can optionally provide context in a textarea, then submit a request to have an AI agent analyze the project (specs, README, CLAUDE.md, AGENTS.md, etc.) and generate a curated list of actionable feature ideas.

The generation process runs in the background via Laravel Queues, allowing users to navigate away while waiting. When ideas are ready, users receive a real-time toast notification via Laravel Reverb WebSockets. The generated ideas are displayed in a list on the page and persisted to the database for future reference.

## Implementation Plan

### Backend Components

**Controller**:

- Create `app/Http/Controllers/BrainstormController.php`
- Methods:
    - `index(Project $project)` - Display brainstorm page with previous sessions
    - `store(Request $request, Project $project)` - Queue brainstorm generation job
    - `show(Project $project, Brainstorm $brainstorm)` - Display specific brainstorm session

**Actions**:

- Create `app/Actions/Brainstorm/GenerateIdeas.php` - Main AI idea generation logic
- Create `app/Actions/Brainstorm/GatherProjectContext.php` - Collect project files for context
- Create `app/Actions/Brainstorm/ParseGeneratedIdeas.php` - Parse AI response into structured ideas

**Jobs**:

- Create `app/Jobs/GenerateBrainstormIdeas.php` (implements `ShouldQueue`)
- Dispatches after user submits form
- Runs in background queue
- Broadcasts completion event via Reverb

**Models**:

- Create `app/Models/Brainstorm.php`
    - Fields:
        - `id` (primary key)
        - `project_id` (foreign key)
        - `user_id` (foreign key, optional)
        - `user_context` (text, nullable) - User-provided context
        - `ideas` (json) - Array of generated ideas
        - `status` (enum: 'pending', 'processing', 'completed', 'failed')
        - `error_message` (text, nullable)
        - `completed_at` (timestamp, nullable)
        - `created_at`, `updated_at`

- Create `app/Models/BrainstormIdea.php` (optional, if ideas need separate tracking)
    - Fields:
        - `id`, `brainstorm_id`, `title`, `description`, `priority`, `category`

**Events**:

- Create `app/Events/BrainstormCompleted.php` (implements `ShouldBroadcast`)
- Create `app/Events/BrainstormFailed.php` (implements `ShouldBroadcast`)
- Broadcasts to project-specific channel via Reverb

**Form Request**:

- Create `app/Http/Requests/StoreBrainstormRequest.php`
    - Validate `user_context` (optional, string, max 5000 chars)

**Routes**:

```php
Route::prefix('projects/{project}')->group(function () {
    Route::get('/brainstorm', [BrainstormController::class, 'index'])->name('projects.brainstorm.index');
    Route::post('/brainstorm', [BrainstormController::class, 'store'])->name('projects.brainstorm.store');
    Route::get('/brainstorm/{brainstorm}', [BrainstormController::class, 'show'])->name('projects.brainstorm.show');
});
```

**Database Migration**:

```php
Schema::create('brainstorms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->text('user_context')->nullable();
    $table->json('ideas')->nullable();
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->text('error_message')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

**AI Agent Integration**:

- Use existing Agent Manager (ClaudeDriver or OpenCodeDriver)
- Construct prompt with project context
- Request structured output (JSON array of ideas)

**Project Context Gathering**:
Automatically read and include:

- `README.md` - Project overview
- `CLAUDE.md` or `AGENTS.md` - Agent instructions
- All `.ai/*.md` files - Custom agent rules
- Spec files from specs table
- Project structure/file tree (limited depth)
- Package dependencies (composer.json, package.json)

**Prompt Engineering**:

```
You are helping brainstorm feature ideas for a Laravel project called "{project_name}".

Project Context:
{README content}
{CLAUDE.md content}
{Spec summaries}
{User-provided context}

Based on this context, generate 10-15 actionable feature ideas that would be valuable additions to this project. For each idea, provide:
- Title (concise, 3-8 words)
- Description (2-3 sentences explaining the feature)
- Priority (high, medium, low)
- Category (feature, enhancement, infrastructure, tooling)

Return response as JSON array.
```

### Frontend Components

**Pages**:

- Create `resources/js/pages/projects/brainstorm.tsx` - Main brainstorm page

**Components**:

- Create `resources/js/components/brainstorm/context-input-form.tsx` - Form with textarea
- Create `resources/js/components/brainstorm/ideas-list.tsx` - Display generated ideas
- Create `resources/js/components/brainstorm/idea-card.tsx` - Individual idea display
- Create `resources/js/components/brainstorm/brainstorm-history.tsx` - Previous sessions
- Create `resources/js/components/brainstorm/loading-state.tsx` - While processing

**WebSocket Integration**:

- Use Laravel Echo to listen for Reverb events
- Subscribe to project-specific channel: `project.{projectId}.brainstorm`
- Listen for `BrainstormCompleted` and `BrainstormFailed` events
- Update UI and show toast notification when event received

**Real-time Notifications**:

- Use toast library (e.g., sonner, react-hot-toast)
- Show notification when ideas are ready
- Include link to view results
- Play subtle sound (optional)

**State Management**:

- Track current brainstorm session status (pending, processing, completed)
- Store generated ideas in component state
- Handle loading states
- Manage WebSocket connection lifecycle

**UI/UX Flow**:

1. **Initial State**:
    - Show textarea for optional context
    - Show "Generate Ideas" button
    - Display previous brainstorm sessions (if any)

2. **Submitting**:
    - Submit form via Inertia
    - Show loading state
    - Display "Processing in background..." message
    - Allow user to navigate away

3. **Background Processing**:
    - Job processes in queue
    - User can continue using app
    - WebSocket connection maintained

4. **Completion**:
    - Receive Reverb event
    - Show toast: "💡 New ideas are ready!"
    - Update ideas list on page (if still on page)
    - Add to history

5. **Display Results**:
    - Show ideas in card grid or list
    - Filter by category/priority
    - Action buttons per idea (Create Spec, Add to Tasks)

### Styling

**Shadcn Components**:

- Use `Textarea` for context input
- Use `Button` for form submit and actions
- Use `Card` for idea cards
- Use `Badge` for priority and category
- Use `Tabs` for filtering ideas (All, Features, Enhancements)
- Use `Skeleton` for loading states
- Use `Alert` for status messages
- Use `Separator` between ideas

**Toast Notifications**:

- Install sonner: `pnpm add sonner`
- Use `<Toaster />` component in layout
- Success toast for completed brainstorms
- Error toast for failed brainstorms

**Idea Card Design**:

- Title (bold, prominent)
- Description (readable, 2-3 lines)
- Priority badge (color-coded: red=high, yellow=medium, gray=low)
- Category badge
- Action buttons (Create Spec, Add to Tasks, Dismiss)

## Acceptance Criteria

- [ ] Brainstorm page displays at `/projects/{project}/brainstorm`
- [ ] Textarea allows users to input optional context (max 5000 chars)
- [ ] "Generate Ideas" button submits form and queues job
- [ ] Form submission creates Brainstorm record with status "pending"
- [ ] Job processes in background (doesn't block request)
- [ ] Job gathers project context (README, CLAUDE.md, specs, etc.)
- [ ] Job calls AI agent with constructed prompt
- [ ] AI response is parsed and stored as JSON
- [ ] Brainstorm status updates to "completed" when done
- [ ] BrainstormCompleted event broadcasts via Reverb
- [ ] Frontend receives WebSocket event
- [ ] Toast notification displays when ideas are ready
- [ ] Generated ideas display in list/grid on page
- [ ] Ideas show title, description, priority, and category
- [ ] Previous brainstorm sessions display in history
- [ ] Users can click on historical session to view ideas
- [ ] Error handling works if AI request fails
- [ ] BrainstormFailed event broadcasts on error
- [ ] Error toast displays with helpful message
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Brainstorm/BrainstormControllerTest.php`

**Key test cases**:

- Test brainstorm index page renders
- Test submitting brainstorm queues job
- Test brainstorm record is created with correct status
- Test user context is saved to database
- Test unauthorized access is prevented
- Test validation prevents context over 5000 chars

**Test file location**: `tests/Feature/Brainstorm/GenerateBrainstormIdeasJobTest.php`

**Key test cases**:

- Test job gathers project context correctly
- Test job constructs AI prompt with all context
- Test job calls AI agent (use FakeAgentDriver)
- Test job parses AI response into ideas array
- Test job updates brainstorm status to "completed"
- Test job stores ideas as JSON
- Test job broadcasts BrainstormCompleted event
- Test job handles AI errors gracefully
- Test job broadcasts BrainstormFailed on error
- Test job sets error_message on failure

### Unit Tests

**Test file location**: `tests/Unit/Actions/GatherProjectContextTest.php`

**Key test cases**:

- Test gathers README.md content
- Test gathers CLAUDE.md content
- Test gathers .ai/ files content
- Test gathers spec summaries
- Test handles missing files gracefully
- Test limits file content size

**Test file location**: `tests/Unit/Actions/ParseGeneratedIdeasTest.php`

**Key test cases**:

- Test parses valid JSON response
- Test extracts title, description, priority, category
- Test handles malformed JSON
- Test handles missing fields
- Test validates idea structure

### Browser Tests

**Test file location**: `tests/Browser/BrainstormPageTest.php`

**Key test cases**:

- Test navigating to brainstorm page
- Test entering context in textarea
- Test submitting form creates brainstorm
- Test loading state displays after submit
- Test user can navigate away while processing
- Test toast notification appears when ideas ready (mock Reverb)
- Test ideas display on page after completion
- Test idea cards show correct data
- Test filtering ideas by category/priority
- Test viewing previous brainstorm sessions
- Test error toast displays on failure
- Test WebSocket connection lifecycle

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Laravel Reverb Setup

**Broadcasting Configuration**:

1. **Define broadcast channel** (`routes/channels.php`):

```php
Broadcast::channel('project.{projectId}.brainstorm', function ($user, $projectId) {
    return $user->hasAccessToProject($projectId);
});
```

2. **BrainstormCompleted event**:

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
            'ideas_count' => count($this->brainstorm->ideas),
            'message' => '💡 New ideas are ready!',
        ];
    }
}
```

3. **Frontend Echo listener**:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
});

// In component
useEffect(() => {
    const channel = window.Echo.private(`project.${projectId}.brainstorm`);

    channel.listen('.brainstorm.completed', (event) => {
        toast.success(event.message);
        router.reload({ only: ['brainstorms'] });
    });

    channel.listen('.brainstorm.failed', (event) => {
        toast.error('Failed to generate ideas: ' + event.error);
    });

    return () => {
        channel.stopListening('.brainstorm.completed');
        channel.stopListening('.brainstorm.failed');
    };
}, [projectId]);
```

### AI Prompt Structure

**Full prompt example**:

```
You are an expert software architect helping brainstorm feature ideas for a Laravel project.

PROJECT: {project_name}

=== PROJECT README ===
{README.md content}

=== AGENT GUIDELINES ===
{CLAUDE.md or AGENTS.md content}

=== EXISTING SPECS ===
{List of current spec titles}

=== USER CONTEXT ===
{user_context or "No additional context provided"}

=== TASK ===
Generate 10-15 actionable, valuable feature ideas for this project. Consider:
- Current project capabilities and patterns
- Gaps in functionality
- Developer experience improvements
- Performance optimizations
- Testing and quality improvements
- Infrastructure enhancements

For each idea, provide:
- title: Short, descriptive name (3-8 words)
- description: Clear explanation of the feature (2-3 sentences)
- priority: "high", "medium", or "low"
- category: "feature", "enhancement", "infrastructure", or "tooling"

Respond with ONLY a JSON array of ideas, no other text:
[
  {
    "title": "Feature title",
    "description": "Feature description...",
    "priority": "high",
    "category": "feature"
  },
  ...
]
```

### Ideas JSON Structure

```json
[
    {
        "title": "API Rate Limiting System",
        "description": "Implement comprehensive rate limiting for all API endpoints using Laravel's built-in throttle middleware. Include configurable limits per user role and endpoint.",
        "priority": "high",
        "category": "feature"
    },
    {
        "title": "Real-time Collaboration Features",
        "description": "Add presence indicators and collaborative editing using Laravel Reverb. Allow multiple users to work on specs and tasks simultaneously.",
        "priority": "medium",
        "category": "feature"
    },
    {
        "title": "Enhanced Error Tracking",
        "description": "Integrate error tracking service to monitor exceptions across worktrees. Provide dashboard for error trends and alerts.",
        "priority": "medium",
        "category": "infrastructure"
    }
]
```

### Queue Configuration

Ensure queue is running:

```bash
php artisan queue:work --queue=brainstorm
```

Or use dedicated queue for brainstorm jobs:

```php
// In job
public $queue = 'brainstorm';
```

### Timeout and Retry Configuration

**Job configuration**:

```php
class GenerateBrainstormIdeas implements ShouldQueue
{
    public $timeout = 300; // 5 minutes
    public $tries = 2;
    public $backoff = [60, 120]; // Retry after 1min, then 2min

    public function handle() {
        // ...
    }

    public function failed(Throwable $exception) {
        $this->brainstorm->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        broadcast(new BrainstormFailed($this->brainstorm, $exception->getMessage()));
    }
}
```

### Security Considerations

**Input Validation**:

- Sanitize user context to prevent prompt injection
- Limit context length to prevent abuse (5000 chars)
- Rate limit brainstorm requests (e.g., max 5 per hour per project)

**File Access**:

- Only read project files within project directory
- Prevent directory traversal
- Limit total context size to prevent memory issues

**AI Response Validation**:

- Validate JSON structure before saving
- Sanitize idea content (prevent XSS)
- Limit number of ideas (max 20)
- Validate required fields exist

### Error Handling

Common errors to handle:

- AI agent timeout
- Invalid JSON response from AI
- Project files not found
- User navigated away (gracefully continue)
- Queue worker not running
- Reverb connection failed

### Future Enhancements

- **Idea Actions**:
    - Convert idea to spec (one-click spec generation)
    - Add idea to task board
    - Export ideas to markdown
    - Share ideas with team

- **Advanced Filtering**:
    - Filter by priority, category
    - Search ideas by keyword
    - Sort by date, relevance

- **Idea Refinement**:
    - "Refine this idea" button to get more details
    - Generate implementation plan for idea
    - Estimate complexity/effort

- **Collaborative Brainstorming**:
    - Multiple users can contribute context
    - Vote on best ideas
    - Comment on ideas

- **Idea Templates**:
    - Saved idea templates
    - Industry-specific suggestions
    - Framework-specific patterns

- **Analytics**:
    - Track which ideas get implemented
    - Show implementation success rate
    - Idea popularity trends

### Performance Optimization

**Context Gathering**:

- Cache project context for 1 hour
- Limit file reading depth (e.g., max 100 files)
- Truncate large files (e.g., max 10KB per file)
- Skip binary and vendor files

**Database**:

- Index `project_id`, `status`, `created_at`
- Consider partitioning old brainstorms
- Archive completed brainstorms after 90 days

### Testing with Fake Driver

**Use FakeAgentDriver in tests**:

```php
use Tests\Fakes\FakeAgentDriver;

$fakeDriver = new FakeAgentDriver();
$fakeDriver->setResponse(json_encode([
    [
        'title' => 'Test Idea',
        'description' => 'Test description',
        'priority' => 'high',
        'category' => 'feature'
    ]
]));

$this->app->instance(AgentDriver::class, $fakeDriver);

// Dispatch job
GenerateBrainstormIdeas::dispatchSync($brainstorm);

// Assert
expect($brainstorm->fresh()->status)->toBe('completed');
expect($brainstorm->fresh()->ideas)->toHaveCount(1);
```

### Integration with Existing Features

**Link to Specs**:

- Add "Create Spec" button on each idea
- Pre-fill spec form with idea title and description

**Link to Tasks**:

- Add "Add to Tasks" button
- Create task from idea with one click

**Link to Agent**:

- Use project's default agent
- Respect agent API key configuration
- Fall back to global agent if project agent not configured
