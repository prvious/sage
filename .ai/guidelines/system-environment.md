# System Environment Guidelines

## Binary Deployment Architecture

This application will be served to users as a self-contained binary. It is designed to interact with the user's system environment and external system dependencies, not with application-managed configuration files.

## Environment and Configuration Handling

### System Environment vs Application Config

**Use System Environment:**

- When interacting with external binaries (e.g., `claude`, `git`, `docker`)
- When checking for system-level environment variables (e.g., `ANTHROPIC_API_KEY`, `PATH`)
- When executing processes that rely on user's shell configuration
- When detecting system capabilities and installed tools

**Use Application Config:**

- For application-specific settings (database connections, app features, etc.)
- For internal application behavior and preferences
- For settings that the binary manages internally

### Process Execution

When executing system commands or external processes:

```php
// ✅ CORRECT - Use system environment
use Illuminate\Support\Facades\Process;

$process = Process::timeout(20)
    ->env(getenv())  // Pass system environment variables
    ->run('claude hello -p --output-format json');
```

```php
// ❌ INCORRECT - Don't rely on Laravel config for system binaries
$binary = config('sage.agents.claude.binary', 'claude');
$process = Process::run("{$binary} whoami");
```

### Environment Variable Access

```php
// ✅ CORRECT - Check system environment directly
if (getenv('ANTHROPIC_API_KEY')) {
    // API key is available in system
}

// ❌ INCORRECT - Don't rely on .env file
if (config('services.anthropic.api_key')) {
    // This checks Laravel's .env, not the user's system
}
```

### Binary Detection and Path Resolution

```php
// ✅ CORRECT - Use ExecutableFinder to locate system binaries
use Symfony\Component\Process\ExecutableFinder;

$finder = new ExecutableFinder();
$path = $finder->find('claude');

// ❌ INCORRECT - Don't configure binary paths in config files
$binary = config('sage.agents.claude.binary');
```

## Key Principles

1. **Self-Contained Binary**: The application binary contains all internal dependencies but relies on the system for external tools and configurations.

2. **System First**: Always prefer system environment variables, PATH resolution, and system configurations over application config files.

3. **No User .env Management**: Users should not need to manage a `.env` file for system-level integrations. The binary should detect and use what's available on their system.

4. **External Dependencies**: The binary should gracefully detect, validate, and report on external system dependencies (like `claude`, `git`, `docker`) without requiring users to configure them in the application.

## Examples

### ✅ Good Example: Agent Installation Check

```php
class CheckAgentInstalled
{
    public function handle(): array
    {
        // Uses system PATH to find binary
        $path = $this->findCommandPath->handle('claude');

        return $path
            ? ['installed' => true, 'path' => $path, 'error_message' => null]
            : ['installed' => false, 'path' => null, 'error_message' => 'Binary not found in PATH'];
    }
}
```

### ✅ Good Example: Agent Authentication Check

```php
class CheckAgentAuthenticated
{
    public function handle(?string $binaryPath = null): array
    {
        // Defaults to 'claude', uses system environment
        $binary = $binaryPath ?? 'claude';

        $process = Process::timeout(20)
            ->env(getenv())  // System environment
            ->run("{$binary} hello -p --output-format json");

        // Check output for authentication status
        if ($process->successful() && !empty($process->output())) {
            return ['authenticated' => true, 'auth_type' => 'cli', 'error_message' => null];
        }

        return ['authenticated' => false, 'auth_type' => 'none', 'error_message' => $process->errorOutput()];
    }
}
```

### ❌ Bad Example: Config-Dependent Checks

```php
// DON'T DO THIS
class CheckAgentStatus
{
    public function handle(): array
    {
        // ❌ Relying on application config instead of system
        $binary = config('sage.agents.claude.binary', 'claude');

        // ❌ Checking Laravel config instead of system env
        if (config('services.anthropic.api_key')) {
            return ['authenticated' => true, 'auth_type' => 'api_key'];
        }

        // This won't work well when distributed as a binary
    }
}
```

## Testing Considerations

When testing system interactions:

```php
// Use Process::fake() for unit tests
it('checks authentication using system environment', function () {
    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $action = new CheckAgentAuthenticated;
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue();
});

// Don't set config values for system-level tests
// ❌ config()->set('sage.agents.claude.binary', 'custom-binary');
```
