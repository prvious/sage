---
name: refactor-generateideas-to-use-agent
description: Refactor GenerateIdeas action to use CLI agent instead of direct API calls
depends_on: null
---

## Feature Description

Currently, the `GenerateIdeas` action in `app/Actions/Brainstorm/GenerateIdeas.php` directly calls the Anthropic API using `Http::post()`. This is inconsistent with the rest of the application's architecture, which uses the Agent abstraction (`AgentManager` and agent drivers) for all AI interactions.

This refactor will:
- Replace direct API calls with the Agent system (using `AgentManager`)
- Leverage the existing Claude CLI agent driver
- Ensure consistent agent usage patterns across the application
- Remove duplicate HTTP API logic in favor of the centralized agent system
- Enable future support for multiple agent types (not just Claude API)

## Implementation Plan

### Backend Components

**Actions to Modify**:
- `app/Actions/Brainstorm/GenerateIdeas.php`:
  - Remove `callAnthropicAPI()` method
  - Remove `use Illuminate\Support\Facades\Http;` import
  - Inject `AgentManager` instead of making direct HTTP calls
  - Use agent driver's `execute()` or similar method to run the prompt
  - Update `handle()` method to use agent system

**Services to Check**:
- Review `app/Services/AgentOutputParser.php` to ensure it can handle brainstorm idea parsing
- May need to create a dedicated parser for brainstorm output if current parser is task-specific

**Agent Driver Interface**:
- Check `app/Drivers/Agent/Contracts/AgentDriverInterface.php` to understand available methods
- Likely will use something similar to how `RunAgent` job uses the driver

**Jobs to Update**:
- `app/Jobs/GenerateBrainstormIdeas.php`:
  - No changes needed (it already injects `GenerateIdeas` action)
  - Verify timeout settings are adequate for agent execution

**No Database Changes**: No migrations or model changes required

**No Route Changes**: API stays the same

### Frontend Components

**No changes required** - This is a backend refactor only. The frontend continues to work the same way.

### Configuration/Infrastructure

**Potential Updates**:
- May need to adjust `config/sage.php` or agent configuration if new settings are needed
- Verify timeout settings for brainstorm generation (currently 300s in job, may need adjustment for CLI agent)

## Acceptance Criteria

- [ ] `GenerateIdeas` action no longer makes direct HTTP API calls
- [ ] `GenerateIdeas` uses `AgentManager` to spawn agent for idea generation
- [ ] Brainstorm generation still produces the same JSON structure of ideas
- [ ] Ideas are successfully parsed and stored in the database
- [ ] Brainstorm status updates (processing → completed/failed) work correctly
- [ ] WebSocket events for brainstorm completion/failure still fire
- [ ] All existing brainstorm tests pass
- [ ] Code is formatted with Pint
- [ ] No direct usage of `Http::` facade for AI API calls remains in GenerateIdeas

## Testing Strategy

### Unit Tests

**Test file location**: `tests/Unit/Actions/Brainstorm/GenerateIdeasTest.php`

Create new unit tests:
- Test that `GenerateIdeas` uses `AgentManager` correctly
- Test prompt construction logic (existing logic)
- Test error handling when agent execution fails
- Mock `AgentManager` and verify it's called with correct parameters

### Feature Tests

**Test file location**: `tests/Feature/Brainstorm/GenerateIdeasIntegrationTest.php`

Update existing tests:
- Ensure integration tests still pass with agent-based generation
- Test complete flow: job dispatch → agent execution → idea parsing → storage
- Test failure scenarios (agent unavailable, parsing errors)

### Browser Tests

**Test file location**: `tests/Browser/Brainstorm/BrainstormGenerationTest.php`

Verify existing browser tests still pass:
- User initiates brainstorm
- Ideas are generated and displayed
- WebSocket updates reflect status changes

## Code Formatting

**PHP Code**: `vendor/bin/pint`

Command to run:
```bash
vendor/bin/pint
```

## Implementation Details

### Current Flow
```
GenerateBrainstormIdeas (Job)
  → GenerateIdeas (Action)
    → callAnthropicAPI() [DIRECT HTTP CALL]
      → Http::post('anthropic.com/v1/messages')
  → ParseGeneratedIdeas (Action)
  → Store in database
```

### New Flow
```
GenerateBrainstormIdeas (Job)
  → GenerateIdeas (Action)
    → AgentManager::driver('claude')
      → ClaudeDriver::execute() or spawn()
        → Runs `claude` CLI with prompt
  → ParseGeneratedIdeas (Action)
  → Store in database
```

### Key Implementation Questions

1. **Agent Driver Method**: Should we use:
   - `spawn()` (like RunAgent job) which returns a Process?
   - Or create a new `execute()` method that's simpler for non-interactive prompts?

2. **Output Parsing**:
   - Current: Expects JSON directly from API response
   - New: May need to extract JSON from agent CLI output
   - Agent output might include formatting/thinking text before JSON

3. **Error Handling**:
   - Current: HTTP exceptions from `Http::post()`
   - New: Process exceptions from agent execution
   - Need to handle agent not available, authentication failures

### Suggested Approach

**Option 1: Use existing `spawn()` method**
```php
public function handle(Project $project, ?string $userContext = null): array
{
    $context = $this->gatherContext->handle($project);
    $prompt = $this->constructPrompt($project, $context, $userContext);

    // Get agent driver
    $driver = $this->agentManager->driver('claude');

    if (!$driver->isAvailable()) {
        throw new \RuntimeException('Claude agent is not available');
    }

    // Execute agent (might need a simplified execute method)
    $process = $driver->spawn(
        worktree: null, // or project path?
        prompt: $prompt,
        options: ['model' => config('services.anthropic.model')]
    );

    $output = $process->getOutput();

    if (!$process->isSuccessful()) {
        throw new \RuntimeException('Agent execution failed: ' . $process->getErrorOutput());
    }

    // Parse JSON from output
    return $this->parseIdeas->handle($output);
}
```

**Option 2: Create new `executePrompt()` method on driver**
```php
// In AgentDriverInterface
public function executePrompt(string $prompt, array $options = []): string;

// In GenerateIdeas
public function handle(Project $project, ?string $userContext = null): array
{
    $context = $this->gatherContext->handle($project);
    $prompt = $this->constructPrompt($project, $context, $userContext);

    $driver = $this->agentManager->driver('claude');

    // Simpler interface for one-shot prompts
    $output = $driver->executePrompt($prompt, [
        'model' => config('services.anthropic.model'),
        'timeout' => 120,
    ]);

    return $this->parseIdeas->handle($output);
}
```

## Additional Notes

### Why This Refactor Matters

1. **Consistency**: All AI interactions should go through the Agent system
2. **Testability**: Easier to mock `AgentManager` than HTTP facade
3. **Flexibility**: Can swap agent implementations without changing brainstorm logic
4. **Centralized Config**: Agent configuration lives in one place
5. **Authentication**: Leverages existing agent authentication checks

### Potential Issues

1. **CLI Output Formatting**: Agent CLI might wrap JSON in markdown code blocks or add thinking text
   - Solution: Update `ParseGeneratedIdeas` to extract JSON from agent output

2. **Timeout Handling**: CLI execution might have different timeout characteristics than HTTP
   - Solution: Ensure job timeout (300s) is longer than agent timeout

3. **Error Messages**: CLI errors might be formatted differently than API errors
   - Solution: Normalize error messages in the action

### Future Enhancements

After this refactor, it will be easier to:
- Support multiple agent types (not just Claude)
- Add streaming progress updates during idea generation
- Reuse brainstorm prompts in other contexts
- Test brainstorm generation with `FakeAgentDriver`

### Breaking Changes

**None** - This is an internal refactor. The external API and behavior remain unchanged.

### Migration Path

1. Update `GenerateIdeas` action to use agent
2. Update tests to mock `AgentManager`
3. Test manually with real agent
4. Deploy and monitor for issues
5. Remove old HTTP-based code

### Testing Checklist

Manual testing before deployment:
- [ ] Create new brainstorm session
- [ ] Verify ideas generate correctly
- [ ] Check WebSocket updates work
- [ ] Verify error handling (e.g., agent not authenticated)
- [ ] Test with different user context inputs
- [ ] Verify JSON parsing still works correctly
