---
name: agent-auth-status-indicators
description: Add authentication and installation status indicators for Claude Code agent
depends_on: null
---

## Feature Description

Enhance the agent settings page to show real-time status indicators for:

1. **Installation Status** - Whether Claude Code CLI is installed and accessible on the system
2. **Authentication Status** - Whether the Claude Code CLI is authenticated with valid credentials

This provides immediate visibility into the agent's readiness without requiring users to run commands manually. The status checks will execute the `claude -p "hello" --output-format json` command and parse the JSON response to determine both installation and authentication state.

## Implementation Plan

### Backend Components

**Create Action**:

- `app/Actions/CheckAgentStatus.php`
    - Single `handle()` method that returns an array with:
        - `installed`: boolean (true if binary found and executable)
        - `authenticated`: boolean (true if `is_error` is false in response)
        - `error_message`: string|null (for display if there's an issue)
    - Use `Symfony\Component\Process\Process` to execute `claude -p "hello" --output-format json`
    - Parse JSON response and check `is_error` field
    - Handle cases where:
        - Binary not found (installation = false)
        - Binary found but returns error (authenticated = false)
        - Binary found and no error (both = true)
    - Timeout after 5 seconds to prevent hanging
    - Use configured binary path from `config('sage.agents.claude.binary')`

**Update Controller**:

- `app/Http/Controllers/ProjectAgentController.php`
    - Modify `index()` method to call `CheckAgentStatus` action
    - Pass status data to Inertia view:
        ```php
        'agentStatus' => [
            'installed' => $status['installed'],
            'authenticated' => $status['authenticated'],
            'error_message' => $status['error_message'],
        ]
        ```

### Frontend Components

**Update Page**:

- `resources/js/pages/projects/agent.tsx`
    - Add status indicator badges/alerts using shadcn Alert component
    - Show different states:
        - **Not Installed**: Red alert with "Claude Code not found. Please install it."
        - **Installed but Not Authenticated**: Yellow alert with "Claude Code found but not authenticated. Run `claude login`."
        - **Installed and Authenticated**: Green success alert with "Claude Code is ready to use."
    - Include the error message if present
    - Use lucide-react icons: `CheckCircle2`, `AlertCircle`, `XCircle`
    - Consider adding a "Check Status" button to re-verify without page reload

**UI Design**:

```tsx
// Pseudocode structure:
<Card>
    <CardHeader>
        <CardTitle>Agent Status</CardTitle>
    </CardHeader>
    <CardContent>
        {!agentStatus.installed && (
            <Alert variant='destructive'>
                <XCircle className='h-4 w-4' />
                <AlertTitle>Not Installed</AlertTitle>
                <AlertDescription>
                    Claude Code CLI is not installed or not in PATH.
                    {agentStatus.error_message}
                </AlertDescription>
            </Alert>
        )}

        {agentStatus.installed && !agentStatus.authenticated && (
            <Alert variant='warning'>
                <AlertCircle className='h-4 w-4' />
                <AlertTitle>Not Authenticated</AlertTitle>
                <AlertDescription>Claude Code is installed but not authenticated. Run `claude login`.</AlertDescription>
            </Alert>
        )}

        {agentStatus.installed && agentStatus.authenticated && (
            <Alert variant='default' className='border-green-500'>
                <CheckCircle2 className='h-4 w-4 text-green-600' />
                <AlertTitle>Ready</AlertTitle>
                <AlertDescription>Claude Code is installed and authenticated.</AlertDescription>
            </Alert>
        )}
    </CardContent>
</Card>
```

### Configuration

**Environment Variables** (optional enhancement):

- `CLAUDE_CODE_PATH` - Already exists in config, defaults to 'claude'
- Can be overridden to use specific path if not in system PATH

## Acceptance Criteria

- [ ] CheckAgentStatus action correctly detects when Claude CLI is not installed
- [ ] CheckAgentStatus action correctly detects when Claude CLI is installed but not authenticated (is_error: true)
- [ ] CheckAgentStatus action correctly detects when Claude CLI is fully authenticated (is_error: false)
- [ ] Agent settings page displays "Not Installed" alert when binary is not found
- [ ] Agent settings page displays "Not Authenticated" alert when installed but not logged in
- [ ] Agent settings page displays "Ready" alert when fully authenticated
- [ ] Status check completes within 5 seconds or times out gracefully
- [ ] Error messages from the CLI are displayed to help users troubleshoot
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Actions/CheckAgentStatusTest.php`

**Key test cases**:

- Test returns installed=false when binary not found
- Test returns installed=true, authenticated=false when binary returns is_error=true
- Test returns installed=true, authenticated=true when binary returns is_error=false
- Test handles timeout gracefully (mock long-running process)
- Test handles malformed JSON response
- Test uses configured binary path from config
- Test handles permission errors

**Test file location**: `tests/Feature/Http/Controllers/ProjectAgentControllerTest.php`

**Update existing tests**:

- Test agent index page includes agentStatus data
- Test agentStatus has correct structure (installed, authenticated, error_message keys)

### Unit Tests

**Test file location**: `tests/Unit/Actions/CheckAgentStatusTest.php`

**Key test cases**:

- Test JSON parsing for authenticated response
- Test JSON parsing for unauthenticated response
- Test binary path resolution from config

### Browser Tests

**Test file location**: `tests/Browser/Agents/AgentStatusIndicatorsTest.php`

**Key test cases**:

- Test page shows "Not Installed" alert when agent not found (mock Process to throw exception)
- Test page shows "Not Authenticated" alert when is_error=true (mock Process response)
- Test page shows "Ready" alert when is_error=false (mock Process response)
- Test alerts use correct colors (red for not installed, yellow for not auth, green for ready)
- Test error messages are displayed when present

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm exec prettier --write "resources/js/**/*.{ts,tsx}"`

## Additional Notes

### JSON Response Parsing

**Unauthenticated Response**:

```json
{
  "type": "result",
  "subtype": "success",
  "is_error": true,
  "result": "Invalid API key · Please run /login",
  ...
}
```

**Authenticated Response**:

```json
{
  "type": "result",
  "subtype": "success",
  "is_error": false,
  "result": "Hello! I'm here to help you with your Laravel project...",
  ...
}
```

The key field to check is `is_error` - if true, the agent is not authenticated.

### Process Execution

Use Symfony Process for executing the CLI command:

```php
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

$process = new Process([
    config('sage.agents.claude.binary'),
    '-p',
    'hello',
    '--output-format',
    'json'
]);

$process->setTimeout(5); // 5 second timeout
$process->run();

if (!$process->isSuccessful()) {
    // Binary not found or failed to execute
    return [
        'installed' => false,
        'authenticated' => false,
        'error_message' => $process->getErrorOutput(),
    ];
}

$output = $process->getOutput();
$data = json_decode($output, true);

return [
    'installed' => true,
    'authenticated' => !($data['is_error'] ?? true),
    'error_message' => $data['is_error'] ?? false ? $data['result'] : null,
];
```

### Error Handling

Handle these edge cases:

- Binary not in PATH or doesn't exist
- Binary exists but not executable (permission error)
- Binary executes but returns malformed JSON
- Process timeout (user's system is slow)
- Unexpected exception during execution

### Future Enhancements

- Add a "Refresh Status" button that re-checks without full page reload
- Cache status for 30 seconds to avoid hammering the CLI on every page load
- Show additional info like Claude Code version when available
- Add troubleshooting links in the error messages
- Show authentication expiry date if available in response
