---
name: command-finder-with-cache
description: Cached binary path resolution using 'which' command
depends_on: null
---

## Feature Description

The agent status check is currently failing even when the agent (Claude Code) is installed because it relies on the binary being in the PATH. This feature implements a reusable `FindCommandPath` action that uses the `which` command to locate binaries and caches the results to improve performance.

This action will be generic enough to find any system binary (claude, php, node, etc.) and will cache the full path for a configurable time period to avoid repeated system calls.

## Problem Statement

Currently, `CheckAgentStatus` uses the binary name directly (e.g., `'claude'`) which may not be found if it's not in the PATH or if the PATH is not properly configured in the context where Laravel runs. Using `which` to resolve the full path will make binary discovery more reliable.

## Implementation Plan

### Backend Components

**Actions:**

- `app/Actions/FindCommandPath.php` - New action to find binary paths using `which` command
    - `handle(string $command): ?string` - Returns full path or null
    - Uses `Symfony\Component\Process\Process` to execute `which {command}`
    - Caches results using Laravel's Cache facade
    - Cache TTL configurable via config (default: 1 hour)
    - Cache key format: `command_path:{command_name}`
    - Returns null if command not found
    - Handles errors gracefully (returns null on failure)

**Modified Actions:**

- `app/Actions/CheckAgentStatus.php` - Update to use `FindCommandPath`
    - Inject `FindCommandPath` action in constructor
    - Call `FindCommandPath::handle($binary)` to resolve path
    - Use the resolved full path when executing the agent check
    - Maintain backward compatibility: if path not found, try original binary name

**Configuration:**

- `config/sage.php` - Add cache TTL configuration
    - Add `'command_path_cache_ttl' => env('COMMAND_PATH_CACHE_TTL', 3600)` (1 hour in seconds)

### Dependencies

- Symfony Process (already in use)
- Laravel Cache facade
- No new package dependencies required

## Acceptance Criteria

- [ ] `FindCommandPath` action successfully locates binaries using `which` command
- [ ] Full path is returned when binary exists (e.g., `/usr/local/bin/claude`)
- [ ] Returns null when binary doesn't exist
- [ ] Results are cached for the configured TTL
- [ ] Cache can be cleared and path is re-resolved
- [ ] `CheckAgentStatus` uses the resolved full path
- [ ] Agent status check works even if binary not in default PATH
- [ ] Action works on macOS, Linux, and Windows (WSL)
- [ ] All existing tests still pass
- [ ] All tests pass
- [ ] Code is formatted according to project standards (Pint + Prettier)

## Testing Strategy

### Unit Tests

**Test file:** `tests/Unit/Actions/FindCommandPathTest.php`

Key test cases:

- Test finding an existing command (e.g., `php`)
- Test finding a non-existent command returns null
- Test caching behavior - second call doesn't execute `which` again
- Test cache expiration and re-resolution
- Test cache key format is correct
- Test error handling when `which` fails

### Feature Tests

**Test file:** `tests/Feature/Actions/FindCommandPathTest.php`

Key test cases:

- Test integration with real `which` command
- Test finding common binaries (php, sh, etc.)
- Test cache persistence across multiple calls
- Test cache invalidation
- Test custom cache TTL from config

**Modified test file:** `tests/Feature/Actions/CheckAgentStatusTest.php`

Additional test cases:

- Test CheckAgentStatus uses FindCommandPath
- Test CheckAgentStatus works with full path from FindCommandPath
- Test fallback when FindCommandPath returns null

### Browser Tests

**Modified test file:** `tests/Browser/AgentStatusIndicatorsTest.php`

- Verify agent status page still works correctly
- No new browser tests needed (existing tests cover the UI)

## Code Formatting

Format all code using Laravel Pint for PHP:

Command to run: `vendor/bin/pint --dirty`

## Implementation Details

### FindCommandPath Action Structure

```php
<?php

namespace App\Actions;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

final readonly class FindCommandPath
{
    /**
     * Find the full path of a command using 'which'.
     *
     * @param  string  $command  The command name to find
     * @return string|null The full path or null if not found
     */
    public function handle(string $command): ?string
    {
        $cacheKey = "command_path:{$command}";
        $cacheTtl = config('sage.command_path_cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($command) {
            try {
                $process = new Process(['which', $command]);
                $process->run();

                if (!$process->isSuccessful()) {
                    return null;
                }

                return trim($process->getOutput()) ?: null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }
}
```

### CheckAgentStatus Updates

```php
// In constructor
public function __construct(
    private readonly FindCommandPath $findCommandPath
) {}

// In handle() method
$binary = config('sage.agents.claude.binary', 'claude') ?: 'claude';

// Try to find the full path
$fullPath = $this->findCommandPath->handle($binary);

// Use full path if found, otherwise fall back to original binary name
$commandToExecute = $fullPath ?? $binary;

$process = new Process([
    $commandToExecute,
    '-p',
    'hello',
    '--output-format',
    'json',
]);
```

## Additional Notes

### Platform Compatibility

- **macOS/Linux:** `which` command is standard
- **Windows (WSL):** `which` is available in WSL environment
- **Windows (native):** `where` command should be used instead (future enhancement)

### Cache Considerations

- Cache is stored in the default cache driver (file/redis/memcached)
- Cache can be manually cleared with `Cache::forget("command_path:{command}")`
- If binary location changes (e.g., after upgrade), cache will need to be cleared or will auto-refresh after TTL
- Consider adding artisan command to clear command path cache: `php artisan cache:forget command_path:*`

### Performance Impact

- First call: ~50-100ms overhead for `which` execution
- Cached calls: <1ms overhead (cache lookup only)
- Net positive: reduces repeated `which` calls during agent status checks

### Security Considerations

- `which` command is safe to execute (read-only operation)
- No user input is executed in shell (command name is sanitized by Process)
- Cache poisoning risk is minimal (only affects path resolution)

### Future Enhancements

- Support for Windows native `where` command
- Global cache clearing command
- Configuration for per-command cache TTLs
- Health check to verify cached paths are still valid
