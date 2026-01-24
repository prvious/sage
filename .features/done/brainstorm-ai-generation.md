---
name: brainstorm-ai-generation
description: Add AI-powered idea generation with background queue jobs
depends_on: brainstorm-core
---

## Feature Description

Enhance the brainstorming feature with AI-powered idea generation. When a user creates a brainstorm, queue a background job that gathers project context, calls the AI agent, and generates actionable feature ideas. Store the generated ideas in the database and update the brainstorm status.

This feature does not include real-time notifications - users will need to refresh or poll to see completed ideas.

## Implementation Plan

### Backend Components

**Actions**:

- Create `app/Actions/Brainstorm/GatherProjectContext.php`
    - Collect README.md content
    - Collect CLAUDE.md or AGENTS.md content
    - Collect .ai/\*.md files
    - Collect spec summaries from database
    - Return structured context array
    - Handle missing files gracefully
    - Limit context size (max 50KB total)

- Create `app/Actions/Brainstorm/ParseGeneratedIdeas.php`
    - Parse JSON response from AI
    - Validate idea structure
    - Extract title, description, priority, category
    - Handle malformed JSON
    - Return validated ideas array

- Create `app/Actions/Brainstorm/GenerateIdeas.php`
    - Use GatherProjectContext to collect data
    - Construct AI prompt
    - Call existing AgentDriver/ClaudeDriver
    - Parse response using ParseGeneratedIdeas
    - Return ideas array

**Job**:

- Create `app/Jobs/GenerateBrainstormIdeas.php` (implements `ShouldQueue`)
    - Accept Brainstorm model in constructor
    - Update status to "processing" at start
    - Call GenerateIdeas action
    - Store ideas in brainstorm
    - Update status to "completed"
    - Set completed_at timestamp
    - Handle errors in failed() method
    - Set status to "failed" and store error message on failure

**Update Controller**:

Modify `BrainstormController::store()` to dispatch the job:

```php
public function store(StoreBrainstormRequest $request, Project $project)
{
    $brainstorm = Brainstorm::create([
        'project_id' => $project->id,
        'user_id' => auth()->id(),
        'user_context' => $request->validated('user_context'),
        'status' => 'pending',
    ]);

    GenerateBrainstormIdeas::dispatch($brainstorm);

    return redirect()
        ->route('projects.brainstorm.index', $project)
        ->with('success', 'Brainstorm queued! Ideas will be generated in the background.');
}
```

**AI Prompt Template**:

```
You are an expert software architect helping brainstorm feature ideas for a Laravel project.

PROJECT: {project_name}

=== PROJECT README ===
{README.md content or "No README found"}

=== AGENT GUIDELINES ===
{CLAUDE.md or AGENTS.md content or "No agent guidelines found"}

=== CUSTOM RULES (.ai/ files) ===
{.ai/*.md files content or "No custom rules found"}

=== EXISTING SPECS ===
{List of spec titles or "No specs found"}

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

### Frontend Components

**Update Pages**:

- Modify `resources/js/pages/projects/brainstorm.tsx`:
    - Show loading state for "processing" brainstorms
    - Display ideas when status is "completed"
    - Show error message when status is "failed"
    - Add refresh button to manually check for updates

**Components**:

- Create `resources/js/components/brainstorm/ideas-list.tsx` - Display generated ideas
- Create `resources/js/components/brainstorm/idea-card.tsx` - Individual idea display
- Create `resources/js/components/brainstorm/loading-state.tsx` - While processing

**Idea Card Design**:

- Title (bold, prominent)
- Description (readable, 2-3 lines)
- Priority badge (color-coded: red=high, yellow=medium, gray=low)
- Category badge

**UI States**:

1. **Pending**: "Queued for processing..."
2. **Processing**: Spinner + "Generating ideas..."
3. **Completed**: Display ideas in grid/list
4. **Failed**: Error alert + retry button

### Styling

**Shadcn Components**:

- Use `Card` for idea cards
- Use `Badge` for priority and category
- Use `Tabs` for filtering ideas (All, Features, Enhancements, etc.)
- Use `Skeleton` for loading states
- Use `Alert` for error messages

## Acceptance Criteria

- [ ] Submitting brainstorm queues GenerateBrainstormIdeas job
- [ ] Job updates status to "processing" at start
- [ ] Job gathers project context (README, CLAUDE.md, specs, .ai/ files)
- [ ] Job constructs AI prompt with all context
- [ ] Job calls existing AgentDriver/ClaudeDriver
- [ ] AI response is parsed and validated
- [ ] Ideas are stored as JSON array in database
- [ ] Status updates to "completed" when done
- [ ] completed_at timestamp is set
- [ ] Frontend displays ideas when completed
- [ ] Error handling works if AI request fails
- [ ] Status updates to "failed" on error
- [ ] Error message is stored and displayed
- [ ] Manual refresh button works
- [ ] Ideas display with correct priority/category badges
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Brainstorm/GenerateBrainstormIdeasJobTest.php`

**Key test cases**:

- Test job updates status to "processing"
- Test job gathers project context correctly
- Test job calls AI agent (use mock/fake)
- Test job parses AI response into ideas array
- Test job updates brainstorm status to "completed"
- Test job stores ideas as JSON
- Test job sets completed_at timestamp
- Test job handles AI errors gracefully
- Test job sets error_message on failure
- Test failed() method updates status to "failed"

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
- Test limits number of ideas (max 20)

### Browser Tests

**Test file location**: `tests/Browser/Brainstorm/IdeaGenerationTest.php`

**Key test cases**:

- Test submitting brainstorm shows "queued" message
- Test processing state displays spinner
- Test completed brainstorm displays ideas
- Test idea cards show correct data
- Test failed brainstorm displays error
- Test refresh button reloads data

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Queue Configuration

Ensure queue worker is running:

```bash
php artisan queue:work
```

Or use dedicated queue:

```php
public $queue = 'brainstorm';
```

### Timeout Configuration

```php
class GenerateBrainstormIdeas implements ShouldQueue
{
    public $timeout = 300; // 5 minutes
    public $tries = 2;
    public $backoff = [60, 120]; // Retry after 1min, then 2min
}
```

### Testing with Mock AI

Mock the AgentDriver in tests:

```php
$mockDriver = Mockery::mock(AgentDriver::class);
$mockDriver->shouldReceive('generate')
    ->once()
    ->andReturn(json_encode([
        [
            'title' => 'Test Idea',
            'description' => 'Test description',
            'priority' => 'high',
            'category' => 'feature'
        ]
    ]));

$this->app->instance(AgentDriver::class, $mockDriver);
```

### Security Considerations

- Sanitize user context to prevent prompt injection
- Limit context length (5000 chars)
- Validate JSON response before saving
- Limit number of ideas (max 20)
- Rate limit brainstorm requests (e.g., max 5 per hour per project)

### Next Steps

After this feature is complete, the next feature (`brainstorm-realtime-notifications`) will:

- Set up Laravel Reverb for WebSocket broadcasting
- Create broadcast events
- Add real-time toast notifications
- Remove need for manual refresh
