---
name: separate-agent-installation-and-auth-checks
description: Split CheckAgentStatus into two independent actions for installation and authentication
depends_on: null
---

## Feature Description

Currently, `CheckAgentStatus` performs both installation verification and authentication checking in a single action. This feature separates these concerns into two independent actions:

1. **CheckAgentInstalled** - Verifies if the Claude Code binary exists and is executable
2. **CheckAgentAuthenticated** - Verifies if the Claude Code CLI is authenticated

This separation provides:

- **Composability**: Can check installation and authentication independently
- **Clarity**: Each action has a single, well-defined responsibility
- **Reusability**: Installation check can be used without triggering authentication check
- **Performance**: Can skip authentication check if installation fails

The JavaScript example provided shows checking authentication using `whoami` command with stdio pipes, which we can replicate in PHP using Symfony Process.

## Implementation Plan

### Backend Components

**New Actions:**

- `app/Actions/CheckAgentInstalled.php` - Simple installation check using FindCommandPath
- `app/Actions/CheckAgentAuthenticated.php` - Authentication check using process execution

**Modified Actions:**

- `app/Actions/CheckAgentStatus.php` - Refactor to use both new actions

**Modified Controllers:**

- `app/Http/Controllers/ProjectAgentController.php` - Use both actions independently in deferred props

**Routes/APIs:**

- No new routes required (existing route remains the same)

### Action Specifications

#### CheckAgentInstalled

```php
/**
 * @return array{installed: bool, path: string|null, error_message: string|null}
 */
public function handle(): array
{
    $binary = config('sage.agents.claude.binary', 'claude') ?: 'claude';

    // If already absolute path, verify it exists
    if (str_starts_with($binary, '/')) {
        return file_exists($binary) && is_executable($binary)
            ? ['installed' => true, 'path' => $binary, 'error_message' => null]
            : ['installed' => false, 'path' => null, 'error_message' => 'Binary not found at configured path'];
    }

    // Use FindCommandPath to locate binary
    $path = $this->findCommandPath->handle($binary);

    return $path
        ? ['installed' => true, 'path' => $path, 'error_message' => null]
        : ['installed' => false, 'path' => null, 'error_message' => 'Binary not found in PATH'];
}
```

#### CheckAgentAuthenticated

```php
/**
 * @return array{authenticated: bool, auth_type: 'cli'|'api_key'|'none', error_message: string|null}
 */
public function handle(?string $binaryPath = null): array
{
    // Check for ANTHROPIC_API_KEY environment variable
    if (config('services.anthropic.api_key')) {
        return [
            'authenticated' => true,
            'auth_type' => 'api_key',
            'error_message' => null,
        ];
    }

    // Resolve binary path
    $binary = $binaryPath ?? config('sage.agents.claude.binary', 'claude') ?: 'claude';

    // Try 'claude whoami' to check CLI authentication
    $process = new Process([$binary, 'whoami']);
    $process->setTimeout(5);
    $process->run();

    $stdout = trim($process->getOutput());
    $stderr = trim($process->getErrorOutput());

    if ($process->isSuccessful() && !empty($stdout) && !str_contains($stderr, 'not authenticated')) {
        return [
            'authenticated' => true,
            'auth_type' => 'cli',
            'error_message' => null,
        ];
    }

    // Extract meaningful error message
    $errorMessage = $stderr ?: 'Not authenticated';

    return [
        'authenticated' => false,
        'auth_type' => 'none',
        'error_message' => $errorMessage,
    ];
}
```

#### Refactored CheckAgentStatus

```php
public function handle(): array
{
    $installCheck = $this->checkAgentInstalled->handle();

    if (!$installCheck['installed']) {
        return [
            'installed' => false,
            'authenticated' => false,
            'error_message' => $installCheck['error_message'],
        ];
    }

    $authCheck = $this->checkAgentAuthenticated->handle($installCheck['path']);

    return [
        'installed' => true,
        'authenticated' => $authCheck['authenticated'],
        'auth_type' => $authCheck['auth_type'] ?? null,
        'error_message' => $authCheck['error_message'],
    ];
}
```

### Controller Changes

**ProjectAgentController:**

```php
public function index(
    Project $project,
    CheckAgentInstalled $checkInstalled,
    CheckAgentAuthenticated $checkAuthenticated
): Response {
    return Inertia::render('projects/agent', [
        'project' => $project,
        'agentInstalled' => Inertia::defer(fn () => $checkInstalled->handle()),
        'agentAuthenticated' => Inertia::defer(fn () => $checkAuthenticated->handle()),
    ]);
}
```

### Frontend Components

**Modified Pages:**

- `resources/js/pages/projects/agent.tsx` - Handle two separate deferred props

**Frontend Type Updates:**

```typescript
interface AgentInstallationStatus {
    installed: boolean;
    path: string | null;
    error_message: string | null;
}

interface AgentAuthenticationStatus {
    authenticated: boolean;
    auth_type: 'cli' | 'api_key' | 'none';
    error_message: string | null;
}

interface Props {
    project: Project;
    agentInstalled?: AgentInstallationStatus;
    agentAuthenticated?: AgentAuthenticationStatus;
}
```

**UI Logic:**

- Show "Not Installed" alert if `!agentInstalled?.installed`
- Show "Not Authenticated" alert if `agentInstalled?.installed && !agentAuthenticated?.authenticated`
- Show "Ready" alert with auth type badge if both checks pass
- Display appropriate error messages from each check

## Acceptance Criteria

- [ ] `CheckAgentInstalled` action created and returns correct structure
- [ ] `CheckAgentAuthenticated` action created with `whoami` command
- [ ] Both actions can be used independently
- [ ] `CheckAgentStatus` refactored to compose both actions
- [ ] Controller uses both actions as separate deferred props
- [ ] Frontend properly handles two separate deferred props with loading states
- [ ] UI shows installation status separately from authentication status
- [ ] API key authentication is detected via environment variable
- [ ] CLI authentication is detected via `whoami` command
- [ ] Error messages are clear and actionable
- [ ] All tests pass
- [ ] Code is formatted according to project standards (Pint for PHP, Prettier for TypeScript)

## Testing Strategy

### Unit Tests

**Test file: `tests/Unit/Actions/CheckAgentInstalledTest.php`**

- Returns installed=true when binary found via FindCommandPath
- Returns installed=false when binary not found
- Returns installed=true when absolute path exists and is executable
- Returns installed=false when absolute path doesn't exist
- Returns correct path in response
- Returns appropriate error messages

**Test file: `tests/Unit/Actions/CheckAgentAuthenticatedTest.php`**

- Returns auth_type='api_key' when ANTHROPIC_API_KEY is set
- Returns auth_type='cli' when whoami succeeds
- Returns auth_type='none' when whoami fails
- Returns authenticated=false when stderr contains 'not authenticated'
- Handles process timeout gracefully
- Returns appropriate error messages

**Test file: `tests/Unit/Actions/CheckAgentStatusTest.php`**

- Returns installed=false when installation check fails
- Returns authenticated=false when authentication check fails
- Returns both true when both checks pass
- Includes auth_type in response
- Passes binary path from installation check to authentication check

### Feature Tests

**Test file: `tests/Feature/Actions/CheckAgentInstalledTest.php`**

- Detects actual claude binary if installed
- Returns false for nonexistent binaries
- Works with configured binary path from config
- Works with absolute paths in config

**Test file: `tests/Feature/Actions/CheckAgentAuthenticatedTest.php`**

- Detects API key from environment
- Runs whoami command on actual binary
- Handles authentication errors from real CLI
- Times out appropriately for unresponsive commands

### Browser Tests

**Test file: `tests/Browser/Projects/AgentStatusPageTest.php`**

- Shows loading skeleton for both checks
- Displays "Not Installed" when installation check fails
- Displays "Not Authenticated" when only auth check fails
- Displays "Ready" with auth type badge when both pass
- Shows API key badge when authenticated via API key
- Shows CLI badge when authenticated via CLI
- Refresh button triggers both checks again

## Code Formatting

**PHP Code:**
Format using: Laravel Pint
Command to run: `vendor/bin/pint --dirty`

**TypeScript Code:**
Format using: Prettier
Command to run: `pnpm run format` (if configured) or `npx prettier --write resources/js/pages/projects/agent.tsx`

## Additional Notes

### Why Separate Actions?

1. **Installation is Fast**: Using `FindCommandPath` is instant - just checking if a file exists
2. **Authentication is Slow**: Running `claude whoami` can take 2-5 seconds
3. **Independent Concerns**: You might want to check installation without triggering auth check
4. **Better Error Messages**: Can provide specific errors for each failure mode
5. **Deferred Props**: Can defer only the slow authentication check while showing installation status immediately

### Authentication Detection Strategy

Following the JavaScript example:

1. **First**: Check for `ANTHROPIC_API_KEY` environment variable (instant)
2. **Second**: Try `claude whoami` command (2-5 seconds)
3. **Fallback**: If whoami times out or errors, return `auth_type='none'`

### Performance Considerations

- Installation check: < 10ms (file system check)
- Authentication check: 2-5 seconds (process execution)
- Using deferred props means page loads instantly, then status updates appear

### Security Considerations

- API keys are checked via config, never exposed to frontend
- Process execution uses explicit binary paths to prevent injection
- Timeout prevents hanging on unresponsive binaries
- Error messages don't expose sensitive authentication details

### Edge Cases

- Binary exists but is not executable (permission issue)
- Binary found but returns unexpected output format
- Authentication process hangs/times out
- Both API key and CLI auth present (prioritize API key)
- Binary path changes between installation and authentication check
