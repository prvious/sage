---
name: simplify-agent-system-claude-only
description: Remove OpenCode driver and API key management, use only Claude Code with built-in auth
depends_on: null
---

## Feature Description

Simplify the agent system by removing the OpenCode agent driver and all API key management functionality. The system will only support Claude Code, leveraging its built-in authentication instead of managing API keys in the application. This reduces complexity and focuses on getting the base functionality working with a single, well-supported agent.

This change removes:

- OpenCode driver implementation
- API key storage and management (both Claude Code and OpenCode)
- API key testing functionality
- Agent selection UI (default to Claude Code only)
- Database columns for storing encrypted API keys

## Implementation Plan

### Backend Components

**Remove Files**:

- `app/Drivers/Agent/OpenCodeDriver.php` - Delete OpenCode driver
- `app/Actions/StoreApiKey.php` - Delete API key storage action
- `app/Actions/TestAgentConnection.php` - Delete connection testing action
- `app/Http/Requests/StoreApiKeyRequest.php` - Delete API key form request

**Modify Agent System**:

- `app/Drivers/Agent/AgentManager.php`
    - Remove `createOpencodeDriver()` method
    - Keep only `createClaudeDriver()` and `createFakeDriver()`
    - Update default driver logic

**Update Models**:

- `app/Models/AgentSetting.php`
    - Remove fields: `claude_code_api_key`, `opencode_api_key`, `claude_code_last_tested_at`, `opencode_last_tested_at`
    - Keep only `default_agent` field (which will always be 'claude-code' for now)
    - Simplify casts to remove encrypted API keys and datetime casts

**Update Controller**:

- `app/Http/Controllers/ProjectAgentController.php`
    - Remove `storeApiKey()` method
    - Remove `testConnection()` method
    - Simplify `index()` method to remove API key related data
    - Remove `updateDefault()` method (no longer needed with single agent)

**Update Actions**:

- `app/Actions/UpdateDefaultAgent.php`
    - Simplify to always set 'claude-code' as default
    - Or remove entirely if not needed

**Database Migration**:

- Create migration to drop columns from `agent_settings` table:
    - `claude_code_api_key`
    - `opencode_api_key`
    - `claude_code_last_tested_at`
    - `opencode_last_tested_at`

**Routes**:

- `routes/web.php`
    - Remove `projects.agent.storeApiKey` route
    - Remove `projects.agent.testConnection` route
    - Remove `projects.agent.updateDefault` route (or keep if still needed for future)

**Configuration**:

- `config/sage.php` (if exists)
    - Remove opencode configuration
    - Simplify agent config to only include claude-code

### Frontend Components

**Update Pages**:

- `resources/js/pages/projects/agent.tsx`
    - Remove API key input forms
    - Remove agent selection dropdown
    - Remove connection test buttons
    - Simplify to show: "Currently using Claude Code with system authentication"
    - Remove all OpenCode references
    - Make it a simple informational page

**Remove Unnecessary UI**:

- Remove any agent selection components
- Remove API key management forms
- Remove connection status indicators

### Testing Strategy

**Update Existing Tests**:

- `tests/Feature/Http/Controllers/ProjectAgentControllerTest.php`
    - Remove tests for `storeApiKey` method
    - Remove tests for `testConnection` method
    - Remove tests for `updateDefault` method
    - Update `index` test to verify simplified response
    - Add test to verify only Claude Code is available

**Update Unit Tests**:

- Remove tests for `StoreApiKey` action
- Remove tests for `TestAgentConnection` action
- Update `AgentManager` tests to verify OpenCode driver is removed
- Update `AgentSetting` model tests to verify removed fields

**Update Factory**:

- `database/factories/AgentSettingFactory.php`
    - Remove API key generation
    - Remove last_tested_at timestamps
    - Simplify to only generate `default_agent` => 'claude-code'

### Database Changes

Create migration: `2026_01_XX_XXXXXX_remove_agent_api_keys_and_opencode.php`

```php
public function up(): void
{
    Schema::table('agent_settings', function (Blueprint $table) {
        $table->dropColumn([
            'claude_code_api_key',
            'opencode_api_key',
            'claude_code_last_tested_at',
            'opencode_last_tested_at',
        ]);
    });
}

public function down(): void
{
    Schema::table('agent_settings', function (Blueprint $table) {
        $table->text('claude_code_api_key')->nullable();
        $table->text('opencode_api_key')->nullable();
        $table->timestamp('claude_code_last_tested_at')->nullable();
        $table->timestamp('opencode_last_tested_at')->nullable();
    });
}
```

## Acceptance Criteria

- [ ] OpenCodeDriver.php file is deleted
- [ ] StoreApiKey.php action is deleted
- [ ] TestAgentConnection.php action is deleted
- [ ] StoreApiKeyRequest.php is deleted
- [ ] AgentManager only has createClaudeDriver() and createFakeDriver()
- [ ] AgentSetting model has no API key or last_tested_at fields
- [ ] ProjectAgentController has no storeApiKey or testConnection methods
- [ ] Migration removes API key columns from database
- [ ] Agent settings page shows "Using Claude Code" with no forms
- [ ] No references to "opencode" or "OpenCode" in codebase
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Http/Controllers/ProjectAgentControllerTest.php`

**Update existing tests**:

- Test agent index page renders with simplified data
- Test that agent settings default to claude-code
- Remove API key storage tests
- Remove connection testing tests

**Test file location**: `tests/Feature/Agents/AgentManagerTest.php` (if exists)

**Key test cases**:

- Test that only claude-code and fake drivers are available
- Test that attempting to use opencode driver throws exception
- Test default driver is claude-code

### Unit Tests

**Test file location**: `tests/Unit/Models/AgentSettingTest.php`

**Key test cases**:

- Test model no longer has API key fields
- Test model no longer has last_tested_at fields
- Test default_agent field exists and works

### Browser Tests

**Test file location**: `tests/Browser/Agents/AgentSettingsPageTest.php`

**Update existing tests**:

- Test page shows "Using Claude Code" message
- Test no API key input forms are visible
- Test no agent selection dropdown exists
- Remove API key submission tests
- Remove connection test button tests

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Migration Strategy

Since we're removing encrypted data columns, we should:

1. Run migration in development first to verify
2. Backup any existing API keys if needed (though user said not to worry)
3. Drop columns in migration

### Future Considerations

- The `default_agent` field in `agent_settings` table could be kept for future expansion
- The AgentManager pattern is still valuable for testing (FakeDriver)
- When re-adding agent support later, we can use this simplified structure as a base
- Authentication checking will be handled in a future feature

### Claude Code Binary Path

- Still configurable via environment variable: `CLAUDE_CODE_PATH`
- Defaults to 'claude' (assumes it's in PATH)
- No authentication check for now (as per user request)

### Simplified Agent Settings Page

The agent page should be very simple now:

- Header: "Agent Settings"
- Info box: "This project uses Claude Code for AI assistance"
- Note: "Claude Code uses your system's authentication"
- No forms, no inputs, just informational

### Testing with FakeDriver

All existing tests that use agents should continue to work because:

- FakeDriver remains available for testing
- Tests can mock the AgentManager
- Process spawning tests will use FakeDriver
