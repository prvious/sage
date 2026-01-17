---
name: agent-management-page
description: Manage project agent settings including default agent selection and API key configuration
depends_on: project-context-sidebar
---

## Feature Description

Create a dedicated agent management page where users can configure agent settings for their project. This includes:

- Setting the default agent (Claude Code or OpenCode)
- Viewing authentication status for each agent
- Managing API keys for Claude Code and OpenCode
- Testing agent connectivity
- Viewing agent-specific configuration options

This centralizes all agent-related configuration in one place, making it easy for users to set up and manage their AI coding assistants.

## Implementation Plan

### Backend Components

**Controller**:

- Update `app/Http/Controllers/AgentController.php` (already exists)
- Add methods:
    - `settings(Project $project)` - Display agent settings page
    - `updateDefault(Request $request, Project $project)` - Set default agent
    - `storeApiKey(Request $request, Project $project)` - Save API key
    - `testConnection(Request $request, Project $project)` - Test agent authentication
    - `getStatus(Project $project)` - Get current agent status

**Actions**:

- Create `app/Actions/Agent/UpdateDefaultAgent.php`
- Create `app/Actions/Agent/StoreApiKey.php`
- Create `app/Actions/Agent/TestAgentConnection.php`
- Create `app/Actions/Agent/GetAgentStatus.php`

**Form Request**:

- Create `app/Http/Requests/UpdateDefaultAgentRequest.php`
    - Validate agent driver (claude, opencode)
- Create `app/Http/Requests/StoreApiKeyRequest.php`
    - Validate API key format
    - Validate agent type

**Database Migration** (if storing per-project settings):

- Add columns to `projects` table:
    - `default_agent` (string, nullable) - 'claude' or 'opencode'
    - `claude_api_key` (encrypted, nullable)
    - `opencode_api_key` (encrypted, nullable)
- Or create separate `project_agent_settings` table

**Routes**:

```php
Route::prefix('projects/{project}')->group(function () {
    Route::get('/agent', [AgentController::class, 'settings'])->name('projects.agent.settings');
    Route::post('/agent/default', [AgentController::class, 'updateDefault'])->name('projects.agent.default');
    Route::post('/agent/api-key', [AgentController::class, 'storeApiKey'])->name('projects.agent.api-key');
    Route::post('/agent/test', [AgentController::class, 'testConnection'])->name('projects.agent.test');
    Route::get('/agent/status', [AgentController::class, 'getStatus'])->name('projects.agent.status');
});
```

**Agent Status Detection**:

- Check if Claude Code is installed: `which claude`
- Check if OpenCode is installed: `which opencode`
- Attempt authentication check for each agent
- Return status: `authenticated`, `not_authenticated`, `not_installed`

**API Key Storage**:

- Encrypt API keys before storing in database
- Use Laravel's `Crypt` facade
- Consider storing in `.env` file instead of database for global settings
- Support both project-level and global API keys

**Agent Configuration**:

- Read from `config/sage.php`:

```php
'agents' => [
    'claude' => [
        'driver' => ClaudeCodeDriver::class,
        'binary' => env('CLAUDE_CODE_PATH', 'claude'),
    ],
    'opencode' => [
        'driver' => OpenCodeDriver::class,
        'binary' => env('OPENCODE_PATH', 'opencode'),
    ],
],
'default' => env('SAGE_AGENT', 'claude'),
```

### Frontend Components

**Page**:

- Create `resources/js/pages/projects/agent.tsx`
- Agent settings interface with sections:
    - Default agent selection
    - Authentication status
    - API key management
    - Test connection
    - Agent-specific settings (future)

**Components**:

- Create `resources/js/components/agent/agent-selector.tsx` - Radio group or select for agent choice
- Create `resources/js/components/agent/auth-status-card.tsx` - Display auth status for each agent
- Create `resources/js/components/agent/api-key-form.tsx` - Form to input API keys
- Create `resources/js/components/agent/connection-test.tsx` - Test button with status

**UI Structure**:

**Section 1: Default Agent**

- Radio buttons or cards to select Claude Code or OpenCode
- Display current default agent
- Save button to update default

**Section 2: Authentication Status**

- Cards showing status for each agent:
    - Agent name and icon
    - Status badge (Authenticated, Not Authenticated, Not Installed)
    - Last checked timestamp
    - "Test Connection" button

**Section 3: API Key Management**

- Tabs or accordion for each agent
- Input field for API key (password type)
- "Save API Key" button
- "Reveal API Key" toggle (show/hide)
- Instructions on how to obtain API key

**Section 4: Agent Information** (optional)

- Agent version
- Binary path
- Configuration details

### Styling

**Shadcn Components**:

- Use `Card` for agent status cards
- Use `RadioGroup` for default agent selection
- Use `Input` (type="password") for API key fields
- Use `Button` for actions (Save, Test, Reveal)
- Use `Badge` for status indicators
- Use `Tabs` for different agent sections
- Use `Alert` for success/error messages
- Use `Separator` between sections

**Status Indicators**:

- **Authenticated**: Green badge with checkmark icon
- **Not Authenticated**: Yellow badge with warning icon
- **Not Installed**: Red badge with X icon

## Acceptance Criteria

- [ ] Agent settings page displays at `/projects/{project}/agent`
- [ ] Users can select default agent (Claude Code or OpenCode)
- [ ] Default agent selection saves successfully
- [ ] Authentication status displays for each agent
- [ ] Users can input and save API keys for each agent
- [ ] API keys are encrypted in database
- [ ] "Test Connection" button verifies agent authentication
- [ ] Connection test results display to user
- [ ] Status badges show correct authentication state
- [ ] Binary path detection works (shows if agent is installed)
- [ ] Form validation prevents invalid API key formats
- [ ] Success/error messages display after operations
- [ ] Settings persist across page reloads
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Agent/AgentControllerTest.php`

**Key test cases**:

- Test settings page renders successfully
- Test updating default agent saves to database
- Test storing API key encrypts and saves
- Test retrieving API key decrypts correctly
- Test connection test calls agent binary
- Test status endpoint returns correct agent states
- Test validation prevents invalid agent types
- Test validation prevents invalid API key formats
- Test unauthorized access is prevented
- Test project-specific settings are isolated

**Test file location**: `tests/Feature/Agent/AgentStatusTest.php`

**Key test cases**:

- Test detecting installed agents (mock `which` command)
- Test authentication check for Claude Code
- Test authentication check for OpenCode
- Test status returns "not_installed" when binary missing
- Test status returns "authenticated" when valid API key
- Test status returns "not_authenticated" when invalid API key

### Browser Tests

**Test file location**: `tests/Browser/AgentManagementTest.php`

**Key test cases**:

- Test navigating to agent settings page
- Test selecting Claude Code as default agent
- Test selecting OpenCode as default agent
- Test default agent selection persists after save
- Test entering API key for Claude Code
- Test API key save shows success message
- Test "Reveal API Key" toggle works
- Test "Test Connection" button triggers test
- Test connection test shows loading state
- Test connection test displays result (success/failure)
- Test status badges display correct colors/icons
- Test different projects have isolated settings
- Test validation errors display for invalid input

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Agent Detection Logic

**Check if agent is installed**:

```php
use Symfony\Component\Process\Process;

$process = new Process(['which', 'claude']);
$process->run();

$isInstalled = $process->isSuccessful();
```

**Check authentication**:

```php
// For Claude Code
$process = new Process(['claude', '--version']); // or auth check command
$process->run();

$isAuthenticated = $process->isSuccessful();
```

### API Key Encryption

**Storing encrypted API key**:

```php
use Illuminate\Support\Facades\Crypt;

$project->update([
    'claude_api_key' => Crypt::encryptString($apiKey),
]);
```

**Retrieving decrypted API key**:

```php
$apiKey = $project->claude_api_key
    ? Crypt::decryptString($project->claude_api_key)
    : null;
```

### Environment Variables vs Database

Consider two approaches for API key storage:

**Option 1: Database (project-specific)**

- Pros: Different API keys per project, encrypted storage
- Cons: More complex, keys in database

**Option 2: Environment Variables (global)**

- Pros: Simpler, follows Laravel conventions
- Cons: Same keys for all projects

**Recommended**: Support both - check project settings first, fallback to env vars.

### Agent Manager Integration

This feature integrates with the existing Agent Manager pattern mentioned in README:

```php
use Illuminate\Support\Manager;

class Agent extends Manager
{
    public function createClaudeDriver(): ClaudeDriver
    {
        return $this->container->make(ClaudeDriver::class);
    }

    public function getDefaultDriver(){
        return config('sage.agents.default', 'claude');
    }
}
```

Update the manager to use project-specific default:

```php
public function getDefaultDriver(){
    $project = app('current.project'); // or from context
    return $project->default_agent ?? config('sage.agents.default', 'claude');
}
```

### API Key Validation

**Claude Code API Key Format**:

- Starts with `sk-ant-`
- Length validation
- Character set validation

**OpenCode API Key Format**:

- Format depends on OpenCode authentication method
- Validate according to OpenCode docs

### Future Enhancements

- **Agent usage statistics** - Track how often each agent is used
- **Cost tracking** - Monitor API usage and costs
- **Agent preferences** - Model selection, temperature, max tokens
- **Multiple API keys** - Rotate between multiple keys
- **Team settings** - Share agent settings across team
- **Agent logs** - View agent execution history
- **Custom agents** - Add custom agent drivers
- **Agent comparison** - Side-by-side comparison of agent capabilities
- **Auto-detect updates** - Check for agent binary updates
- **Rate limiting** - Configure rate limits per agent

### Error Handling

Handle common errors:

- Agent binary not found
- Authentication failed
- Invalid API key format
- Network timeout during connection test
- Encryption/decryption errors
- Database save errors

### Security Considerations

- **API Key Security**:
    - Never log API keys
    - Encrypt at rest
    - Use HTTPS for transmission
    - Mask keys in UI (show last 4 characters)
    - Implement key rotation policy

- **Access Control**:
    - Only project members can view/edit settings
    - Audit log for API key changes
    - Require re-authentication for sensitive operations

### Testing Agent Drivers

**Use FakeAgentDriver for tests**:

```php
use Tests\Fakes\FakeAgentDriver;

// In test
$this->app->instance(ClaudeDriver::class, new FakeAgentDriver());
```

This allows testing without actual agent binaries or API calls.
